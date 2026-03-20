<?php

use App\Enums\PersonnelStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Personnel;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('exposes pagination metadata on a list endpoint', function () {
    $school = ownerWithActiveSubscription()->test_school;
    $staff = staffMember($school, RoleEnum::Admin);

    // 15 par page côté contrôleur : on en crée assez pour ouvrir une 2e page.
    foreach (range(1, 17) as $i) {
        Personnel::create([
            'user_id' => User::factory()->create()->id,
            'school_id' => $school->id,
            'status' => PersonnelStatusEnum::Active,
            'registration_number' => 'EMP-PAG-'.$i,
        ]);
    }

    $response = test()->withToken($staff->test_token)
        ->getJson('/api/v1/personnel')
        ->assertOk();

    // La liste reste directement sous `data` : le contrat existant ne bouge pas.
    expect($response->json('data'))->toBeArray()->toHaveCount(15);

    $meta = $response->json('meta');

    expect($meta)->not->toBeNull()
        ->and($meta['current_page'])->toBe(1)
        ->and($meta['per_page'])->toBe(15)
        ->and($meta['from'])->toBe(1)
        ->and($meta['to'])->toBe(15);

    // owner + admin + les 17 créés ci-dessus.
    expect($meta['total'])->toBe(19)
        ->and($meta['last_page'])->toBe(2);
});

it('reports the second page correctly', function () {
    $school = ownerWithActiveSubscription()->test_school;
    $staff = staffMember($school, RoleEnum::Admin);

    foreach (range(1, 17) as $i) {
        Personnel::create([
            'user_id' => User::factory()->create()->id,
            'school_id' => $school->id,
            'status' => PersonnelStatusEnum::Active,
            'registration_number' => 'EMP-PAG-'.$i,
        ]);
    }

    $meta = test()->withToken($staff->test_token)
        ->getJson('/api/v1/personnel?page=2')
        ->assertOk()
        ->json('meta');

    expect($meta['current_page'])->toBe(2)
        ->and($meta['from'])->toBe(16)
        ->and($meta['to'])->toBe(19);
});

it('adds no meta key to a non-paginated payload', function () {
    $response = ApiResponse::success('Sans pagination.', ['id' => 'abc']);

    expect($response->getData(true))->not->toHaveKey('meta');
});

it('keeps an explicitly provided meta untouched', function () {
    $paginator = new LengthAwarePaginator([1, 2], 2, 1, 1);

    $payload = ApiResponse::success('Meta explicite.', $paginator, 200, ['custom' => true])->getData(true);

    expect($payload['meta'])->toBe(['custom' => true]);
});

it('degrades gracefully for a paginator that does not know its total', function () {
    // simplePaginate() renvoie un Paginator : pas de total, mais on sait s'il
    // reste une page. Le front doit pouvoir afficher « Suivant » quand même.
    $paginator = new Paginator([1, 2, 3], 2, 1);

    $meta = ApiResponse::success('Simple.', $paginator)->getData(true)['meta'];

    expect($meta)->toHaveKey('has_more_pages')
        ->and($meta['has_more_pages'])->toBeTrue()
        ->and($meta)->not->toHaveKey('total');
});
