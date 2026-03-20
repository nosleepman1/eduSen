<?php

namespace App\Services\Api\V1\Platform;

use App\Enums\RegistrationRequestStatusEnum;
use App\Events\SchoolRegistrationRequested;
use App\Repositories\Api\V1\SchoolRegistrationRepository;

class SchoolRegistrationService
{
    public function __construct(private SchoolRegistrationRepository $registrations) {}

    public function submitRequest(array $data): array
    {
        $registration = $this->registrations->create(array_merge($data, [
            'status' => RegistrationRequestStatusEnum::Pending,
        ]));

        event(new SchoolRegistrationRequested($registration));

        return ['registration_request' => $registration];
    }





    public function pendingRequests(): mixed
    {
        return $this->registrations->pendingPaginated();
    }
}
