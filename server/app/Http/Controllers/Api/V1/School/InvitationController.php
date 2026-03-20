<?php

namespace App\Http\Controllers\Api\V1\School;

use App\Http\Controllers\Controller;
use App\Http\Resources\School\InvitationResource;
use App\Models\Invitation;
use App\Services\Api\V1\School\InvitationService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function __construct(
        private InvitationService $invitationService
    ) {}

    public function index(): JsonResponse
    {
        $invitations = Invitation::where('school_id', TenantHelper::currentSchoolId())
            ->with('creator:id,firstname,lastname')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return ApiResponse::success('Liste des invitations', InvitationResource::collection($invitations));
    }

    public function show(Invitation $invitation): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($invitation->school_id);

        return ApiResponse::success('Invitation.', new InvitationResource($invitation->load('creator:id,firstname,lastname')));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'role' => 'required|string|in:OWNER,ADMIN,TEACHER,SUPERVISOR,ACCOUNTANT,SECRETARY,LIBRARIAN',
        ]);

        $invitation = $this->invitationService->create(
            $validated,
            TenantHelper::currentSchoolId(),
            $request->user()
        );

        return ApiResponse::success('Invitation créée et envoyée avec succès.', new InvitationResource($invitation), 201);
    }

    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $result = $this->invitationService->accept($validated['token'], $validated['password']);

        return ApiResponse::success('Invitation acceptée. Votre compte a été créé.', $result);
    }

    public function resend(Invitation $invitation): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($invitation->school_id);

        $this->invitationService->resend($invitation);

        return ApiResponse::success('L\'invitation a été renvoyée.');
    }

    public function cancel(Invitation $invitation): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($invitation->school_id);

        $this->invitationService->cancel($invitation);

        return ApiResponse::success('L\'invitation a été annulée.');
    }

    public function destroy(Invitation $invitation): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($invitation->school_id);

        $invitation->delete();

        return ApiResponse::success('L\'invitation a été supprimée.');
    }
}
