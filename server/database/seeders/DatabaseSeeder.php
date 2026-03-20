<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Super Admin
        $superAdminEmail = env('SUPER_ADMIN_EMAIL', 'abdallahdiouf.dev@gmail.com');
        $superAdminPassword = env('SUPER_ADMIN_PASSWORD', 'Khoudia1970');

        $superAdmin = User::where('email', $superAdminEmail)->first();
        if (!$superAdmin) {
            $superAdmin = User::create([
                'firstname' => 'Abdallah',
                'lastname' => 'Diouf',
                'email' => $superAdminEmail,
                'password' => Hash::make($superAdminPassword),
                'status' => \App\Enums\UserStatusEnum::Active,
                'email_verified_at' => now(),
            ]);

            $superAdmin->roles()->create([
                'role' => \App\Enums\RoleEnum::SuperAdmin,
                'school_id' => null, // Super Admin has no specific school
                'assigned_at' => now(),
            ]);
        }
        // Seed Billing Cycles
        $cycles = [
            ['code' => 'monthly', 'name' => 'Mensuel', 'duration_days' => 30, 'discount_percentage' => 0.00],
            ['code' => 'quarterly', 'name' => 'Trimestriel', 'duration_days' => 90, 'discount_percentage' => 4.00],
            ['code' => 'semiannual', 'name' => 'Semestriel', 'duration_days' => 180, 'discount_percentage' => 8.00],
            ['code' => 'annual', 'name' => 'Annuel', 'duration_days' => 365, 'discount_percentage' => 15.00],
        ];

        foreach ($cycles as $c) {
            \App\Models\BillingCycle::query()->updateOrCreate(['code' => $c['code']], $c);
        }

        // Seed Features
        $featuresData = [
            ['code' => 'attendance_analytics', 'name' => 'Analyses de Présence', 'type' => \App\Enums\FeatureTypeEnum::BOOLEAN],
            ['code' => 'sms_notifications', 'name' => 'Notifications SMS', 'type' => \App\Enums\FeatureTypeEnum::BOOLEAN],
            ['code' => 'ai_reports', 'name' => 'Rapports IA', 'type' => \App\Enums\FeatureTypeEnum::BOOLEAN],
            ['code' => 'api_access', 'name' => 'Accès API', 'type' => \App\Enums\FeatureTypeEnum::BOOLEAN],
        ];

        $features = [];
        foreach ($featuresData as $fd) {
            $features[$fd['code']] = \App\Models\Feature::query()->updateOrCreate(['code' => $fd['code']], $fd);
        }

        // Seed Subscription Plans
        $plans = [
            'Basic' => [
                'price' => 5000.00,
                'max_students' => 200,
                'max_teachers' => 20,
                'features_list' => [
                    'attendance_analytics' => ['active' => true, 'limit' => null],
                    'sms_notifications' => ['active' => false, 'limit' => null],
                    'ai_reports' => ['active' => false, 'limit' => null],
                    'api_access' => ['active' => false, 'limit' => null],
                ]
            ],
            'Premium' => [
                'price' => 15000.00,
                'max_students' => 1000,
                'max_teachers' => 100,
                'features_list' => [
                    'attendance_analytics' => ['active' => true, 'limit' => null],
                    'sms_notifications' => ['active' => true, 'limit' => null],
                    'ai_reports' => ['active' => true, 'limit' => null],
                    'api_access' => ['active' => false, 'limit' => null],
                ]
            ],
            'Enterprise' => [
                'price' => 50000.00,
                'max_students' => 99999,
                'max_teachers' => 99999,
                'features_list' => [
                    'attendance_analytics' => ['active' => true, 'limit' => null],
                    'sms_notifications' => ['active' => true, 'limit' => null],
                    'ai_reports' => ['active' => true, 'limit' => null],
                    'api_access' => ['active' => true, 'limit' => null],
                ]
            ]
        ];

        foreach ($plans as $name => $p) {
            $planModel = \App\Models\SubscriptionPlan::query()->updateOrCreate(['name' => $name], [
                'price' => $p['price'],
                'currency' => 'FCFA',
                'type' => \App\Enums\SubscriptionPlanTypeEnum::Monthly,
                'duration' => 1,
                'max_students' => $p['max_students'],
                'max_teachers' => $p['max_teachers'],
                'features' => array_keys($p['features_list']),
                'is_active' => true,
            ]);

            foreach ($p['features_list'] as $featCode => $config) {
                $featModel = $features[$featCode];
                \App\Models\PlanFeature::query()->updateOrCreate([
                    'subscription_plan_id' => $planModel->id,
                    'feature_id' => $featModel->id,
                ], [
                    'active' => $config['active'],
                    'limit_value' => $config['limit'],
                ]);
            }
        }
    }
}
