<?php

namespace App\Services\Api\V1\Auth;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function issue(string $phoneNumber, ?string $ipAddress = null): string
    {
        $code = (string) random_int(100000, 999999);

        OtpCode::query()->create([
            'phone_number' => $phoneNumber,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'ip_address' => $ipAddress,
        ]);

        return $code;
    }
}
