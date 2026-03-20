<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingCycle;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class BillingCycleController extends Controller
{
    public function index(): JsonResponse
    {
        $cycles = BillingCycle::query()
            ->where('active', true)
            ->get();

        return ApiResponse::success('Liste des cycles de facturation.', $cycles);
    }
}
