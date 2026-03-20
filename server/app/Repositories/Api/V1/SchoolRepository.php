<?php

namespace App\Repositories\Api\V1;

use App\Models\School;
use App\Models\SchoolDetails;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;

class SchoolRepository
{
    public function create(array $data): School
    {
        return School::query()->create($data);
    }

    public function createDetails(School $school, array $data = []): SchoolDetails
    {
        return $school->details()->create(array_merge([
            'primary_color' => '#bc01f5ff',
            'secondary_color' => '#FFFFFF',
            'school_type' => 'MIXED',
            'sector' => 'PRIVATE_SECULAR',
        ], $data));
    }

    public function updateDetails(School $school, array $data): SchoolDetails
    {
        return SchoolDetails::query()->updateOrCreate(['school_id' => $school->id], $data);
    }

    public function createSubscription(School $school, SubscriptionPlan $plan): Subscription
    {
        return $school->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths($plan->duration)->toDateString(),
            'status' => 'ACTIVE',
        ]);
    }

    public function defaultPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::query()->where('is_active', true)->oldest()->first()
            ?? SubscriptionPlan::query()->create([
                'name' => 'Starter',
                'price' => 0,
                'type' => 'MONTHLY',
                'duration' => 1,
                'max_students' => 250,
                'max_teachers' => 25,
                'features' => ['auth', 'setup'],
            ]);
    }
}
