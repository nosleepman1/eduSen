<?php

use App\Enums\RoleEnum;
use App\Models\School;
use App\Models\User;
use App\Notifications\SchoolSetupCompletedNotification;
use Illuminate\Support\Facades\Notification;

function ownerWithSchool(int $setupStep = 0, bool $configured = false): User
{
    $owner = User::factory()->create(['password_changed_at' => now()]);
    $school = School::factory()->create([
        'owner_id' => $owner->id,
        'setup_step' => $setupStep,
        'is_school_setup' => $configured,
    ]);
    
    \App\Models\Personnel::create([
        'user_id' => $owner->id,
        'school_id' => $school->id,
        'status' => \App\Enums\PersonnelStatusEnum::Active,
        'registration_number' => 'EMP-001'
    ]);
    
    \App\Models\UserRole::create([
        'user_id' => $owner->id,
        'school_id' => $school->id,
        'role' => RoleEnum::Owner,
        'is_active' => true,
        'assigned_at' => now()
    ]);

    // Use a real token for authentication to support IsolateBySchool token abilities
    $token = $owner->createToken('api', ['school_id' => $school->id])->plainTextToken;
    $owner->test_token = $token;

    return $owner;
}

it('saves basic school info during setup', function (): void {
    $owner = ownerWithSchool();

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)->postJson('/api/v1/school/setup/basic-info', [
        'primary_color' => '#123ABC',
        'secondary_color' => '#FFFFFF',
        'school_type' => 'MIXED',
        'sector' => 'PRIVATE_SECULAR',
    ])->assertOk();

    $this->assertDatabaseHas('school_details', ['school_id' => $owner->ownedSchool->id, 'primary_color' => '#123ABC']);
});

it('rejects invalid colors and contact info without a primary phone', function (): void {
    $owner = ownerWithSchool();

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)->postJson('/api/v1/school/setup/basic-info', [
        'primary_color' => 'blue',
        'school_type' => 'MIXED',
        'sector' => 'PRIVATE_SECULAR',
    ])->assertUnprocessable();

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)->postJson('/api/v1/school/setup/contact-info', [
        'address' => ['region' => 'Dakar', 'city' => 'Dakar'],
        'phones' => [['phone_number' => '+221771234567', 'type' => 'MOBILE', 'is_primary' => false]],
    ])->assertUnprocessable();
});

it('saves contact info and completes the wizard after both steps', function (): void {
    Notification::fake();
    $owner = ownerWithSchool(1);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)->postJson('/api/v1/school/setup/contact-info', [
        'address' => ['region' => 'Dakar', 'city' => 'Dakar'],
        'phones' => [['phone_number' => '+221771234567', 'type' => 'MOBILE', 'is_primary' => true]],
    ])->assertOk();

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)->postJson('/api/v1/school/setup/complete')
        ->assertOk()
        ->assertJsonPath('data.school.is_school_setup', true);

    Notification::assertSentTo($owner, SchoolSetupCompletedNotification::class);
});

it('sends the queueable SchoolSetupCompletedNotification with correct details upon completion', function () {
    Notification::fake();

    $owner = ownerWithSchool(2);
    $school = $owner->ownedSchool;

    $school->details()->create([
        'primary_color' => '#123456',
        'secondary_color' => '#abcdef',
        'slogan' => 'Mon Slogan Scolaire',
        'website' => 'https://ecole-test.com',
        'logo' => 'schools/logos/test.png',
    ]);
    
    $school->addresses()->create([
        'country' => 'Sénégal',
        'region' => 'Dakar',
        'city' => 'Dakar Plateau',
        'commune' => 'Plateau Commune',
        'street' => 'Rue de l’Aéroport',
        'is_primary' => true,
    ]);

    $school->phones()->create([
        'phone_number' => '771234567',
        'type' => \App\Enums\PhoneTypeEnum::Mobile,
        'dial_code' => '+221',
        'is_primary' => true,
    ]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)->postJson('/api/v1/school/setup/complete')
        ->assertOk();

    Notification::assertSentTo($owner, SchoolSetupCompletedNotification::class, function ($notification) use ($school) {
        expect($notification)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
        
        $mail = $notification->toMail($school->owner);
        $html = (string) $mail->render();
        
        expect($html)->toContain('Configuration de votre école terminée')
            ->toContain($school->name)
            ->toContain('#123456')
            ->toContain('#abcdef')
            ->toContain('Mon Slogan Scolaire')
            ->toContain('Plateau Commune')
            ->toContain('771234567');

        return $notification->school->id === $school->id;
    });
});

