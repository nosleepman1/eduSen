<?php

namespace App\Http\Controllers\Api\V1\School;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Personnel;
use App\Models\SubscriptionInvoice;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SchoolDashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $schoolId = TenantHelper::currentSchoolId();

        if (!$schoolId) {
            return ApiResponse::error('Aucune école associée.', 403);
        }

        // Basic counts
        $studentsCount = Student::where('school_id', $schoolId)->count();
        $teachersCount = Teacher::whereHas('personnel', function ($query) use ($schoolId) {
            $query->where('school_id', $schoolId);
        })->count();
        $personnelCount = Personnel::where('school_id', $schoolId)->count();
        $classesCount = 0; // TODO: Implement classes when model is ready

        // Financials (Current month invoices)
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        $monthlyPayments = SubscriptionInvoice::where('school_id', $schoolId)
            ->where('status', 'PAID')
            ->whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount');

        // Recent Invoices / Activities
        $recentInvoices = SubscriptionInvoice::where('school_id', $schoolId)
            ->with(['subscription.plan', 'subscription.billingCycle'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Simulate Chart Data (For now, just some placeholder mixed with real monthly payments)
        // Ideally we would group by month for the current year
        $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
        
        $enrollmentsData = [
            'labels' => $months,
            'data' => array_fill(0, 12, 0) // Placeholder until Student registration dates are aggregated
        ];
        
        $paymentsData = [
            'labels' => $months,
            'data' => array_fill(0, 12, 0)
        ];

        // Aggregate payments by month for current year
        $invoicesThisYear = SubscriptionInvoice::where('school_id', $schoolId)
            ->where('status', 'PAID')
            ->whereYear('paid_at', Carbon::now()->year)
            ->get();

        foreach ($invoicesThisYear as $invoice) {
            $monthIndex = Carbon::parse($invoice->paid_at)->month - 1;
            $paymentsData['data'][$monthIndex] += $invoice->amount;
        }

        return ApiResponse::success('Statistiques du tableau de bord', [
            'counts' => [
                'students' => $studentsCount,
                'teachers' => $teachersCount,
                'personnel' => $personnelCount,
                'classes' => $classesCount,
                'monthly_payments' => $monthlyPayments
            ],
            'recent_activities' => $recentInvoices,
            'charts' => [
                'enrollments' => $enrollmentsData,
                'payments' => $paymentsData
            ]
        ]);
    }
}
