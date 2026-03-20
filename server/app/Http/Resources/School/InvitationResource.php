<?php

namespace App\Http\Resources\School;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'role' => $this->role?->value,
            'status' => $this->status,
            // Not status alone: an invitation past expires_at can still read
            // PENDING until something touches it. See Invitation::isExpired().
            'is_expired' => $this->isExpired(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'full_name' => trim($this->creator->firstname.' '.$this->creator->lastname),
            ]),
            // Deliberately omitted: `token`. It is the invitation link's secret
            // and has no reason to reach a browser that isn't the invitee's.
        ];
    }
}
