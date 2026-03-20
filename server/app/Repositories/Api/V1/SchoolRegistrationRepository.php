<?php

namespace App\Repositories\Api\V1;

use App\Models\SchoolRegistrationRequest;

class SchoolRegistrationRepository
{
    public function create(array $data): SchoolRegistrationRequest
    {
        return SchoolRegistrationRequest::query()->create($data);
    }

    public function pendingPaginated(int $perPage = 15): mixed
    {
        return SchoolRegistrationRequest::query()->pending()->latest()->paginate($perPage);
    }
}
