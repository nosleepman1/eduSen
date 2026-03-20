@extends('emails.layouts.professional')

@section('title', $title)

@section('content')
    @php
        $primaryColor = '#bc01f5';
        $secondaryColor = '#ffffffff';
        $school = $subscription->school;
        $planName = $subscription->plan_name_snapshot ?? ($subscription->plan ? $subscription->plan->name : 'Plan');
        $price = $subscription->plan_price_snapshot;
        $billingCycleName = $subscription->billing_cycle_snapshot ?? 'N/A';
    @endphp

    <style>
        .section-title {
            color: {{ $primaryColor }};
            font-size: 16px;
            font-weight: 700;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 8px;
            margin-top: 30px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-card {
            background: #fafbfc;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #f0f2f5;
            margin-bottom: 20px;
        }
    </style>

    <h2>{{ $title }}</h2>
    <p>Bonjour,</p>
    <p>{!! $introMessage !!}</p>

    <div class="section-title">Détails de l'abonnement</div>
    <table class="details-table">
        <tr>
            <th>Établissement</th>
            <td><strong>{{ $school->name }}</strong> ({{ $school->code }})</td>
        </tr>
        <tr>
            <th>Plan souscrit</th>
            <td><span class="badge">{{ $planName }}</span></td>
        </tr>
        <tr>
            <th>Cycle de facturation</th>
            <td>{{ $billingCycleName }}</td>
        </tr>
        <tr>
            <th>Statut actuel</th>
            <td>
                <strong>
                    @switch($subscription->status->value)
                        @case('TRIAL')
                            Période d'essai
                            @break
                        @case('TRIAL_EXPIRED')
                            Essai expiré (Période de grâce)
                            @break
                        @case('ACTIVE')
                            Actif
                            @break
                        @case('GRACE_PERIOD')
                            Période de grâce
                            @break
                        @case('EXPIRED')
                            Expiré
                            @break
                        @case('CANCELLED')
                            Annulé
                            @break
                        @case('SUSPENDED')
                            Suspendu
                            @break
                        @default
                            {{ $subscription->status->value }}
                    @endswitch
                </strong>
            </td>
        </tr>
        <tr>
            <th>Prix payé / dû</th>
            <td>{{ number_format($price, 2, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <th>Date de début</th>
            <td>{{ $subscription->starts_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <th>Date de fin / expiration</th>
            <td>{{ $subscription->ends_at->format('d/m/Y H:i') }}</td>
        </tr>
        @if($subscription->grace_ends_at)
            <tr>
                <th>Date limite de grâce</th>
                <td style="color: #dc2626; font-weight: bold;">{{ $subscription->grace_ends_at->format('d/m/Y H:i') }}</td>
            </tr>
        @endif
        @if($subscription->cancellation_reason)
            <tr>
                <th>Raison de l'annulation</th>
                <td>{{ $subscription->cancellation_reason }}</td>
            </tr>
        @endif
    </table>

    @if(isset($actionUrl))
        <div class="btn-container">
            <a href="{{ $actionUrl }}" class="btn" target="_blank">{{ $actionText ?? 'Gérer mon abonnement' }}</a>
        </div>
    @endif

    <p>Si vous avez des questions ou si vous rencontrez des difficultés, n'hésitez pas à contacter notre support client.</p>
    <p>L'équipe support Galsen School</p>
@endsection
