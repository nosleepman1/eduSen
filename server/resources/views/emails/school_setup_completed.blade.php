@extends('emails.layouts.professional')

@section('title', 'Configuration de votre école terminée !')

@section('content')
    @php
        $details = $school->details;
        $primaryColor = $details->primary_color ?? '#bc01f5';
        $secondaryColor = $details->secondary_color ?? '#ffffffff';
        $address = $school->addresses->firstWhere('is_primary', true) ?? $school->addresses->first();
        $logoUrl = $details && $details->logo ? asset('storage/' . $details->logo) : null;
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
    @endphp

    <style>
        .header {
            background-color: {{ $primaryColor }} !important;
        }
        .btn {
            background-color: {{ $primaryColor }} !important;
            box-shadow: 0 4px 6px -1px {{ $primaryColor }}33, 0 2px 4px -1px {{ $primaryColor }}1a !important;
        }
        .btn:hover {
            background-color: {{ $secondaryColor }} !important;
        }
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
        .color-preview {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
            margin-right: 8px;
        }
        .logo-container {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background-color: #fafbfc;
            border-radius: 12px;
            border: 1px dashed #e5e7eb;
        }
        .logo-img {
            max-height: 80px;
            max-width: 200px;
            object-fit: contain;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        .info-card {
            background: #fafbfc;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #f0f2f5;
        }
    </style>

    <h2>Félicitations, la configuration est terminée !</h2>
    <p>Bonjour {{ $owner->firstname }} {{ $owner->lastname }},</p>
    <p>Nous avons le plaisir de vous informer que la configuration initiale de votre établissement <strong>{{ $school->name }}</strong> a été complétée avec succès. Votre plateforme scolaire est désormais prête à être utilisée !</p>

    @if($logoUrl)
        <div class="logo-container">
            <p style="margin-bottom: 8px; font-size: 12px; color: #9ca3af; text-transform: uppercase;">Logo de l'établissement</p>
            <img src="{{ $logoUrl }}" alt="Logo de {{ $school->name }}" class="logo-img">
        </div>
    @endif

    <div class="section-title">Informations de base</div>
    <table class="details-table">
        <tr>
            <th>Nom de l'école</th>
            <td>{{ $school->name }}</td>
        </tr>
        <tr>
            <th>Code unique</th>
            <td><span class="badge">{{ $school->code }}</span></td>
        </tr>
        <tr>
            <th>NINEA</th>
            <td>{{ $school->ninea }}</td>
        </tr>
        <tr>
            <th>Email officiel</th>
            <td>{{ $school->email }}</td>
        </tr>
        @if($details && $details->website)
            <tr>
                <th>Site Internet</th>
                <td><a href="{{ $details->website }}" target="_blank" style="color: {{ $primaryColor }};">{{ $details->website }}</a></td>
            </tr>
        @endif
        @if($details && $details->school_type)
            <tr>
                <th>Type d'école</th>
                <td>{{ $details->school_type->value }}</td>
            </tr>
        @endif
        @if($details && $details->sector)
            <tr>
                <th>Secteur</th>
                <td>{{ $details->sector->value }}</td>
            </tr>
        @endif
    </table>

    <div class="section-title">Charte Graphique</div>
    <table class="details-table">
        <tr>
            <th>Couleur Primaire</th>
            <td>
                <span class="color-preview" style="background-color: {{ $primaryColor }};"></span>
                <code>{{ $primaryColor }}</code>
            </td>
        </tr>
        <tr>
            <th>Couleur Secondaire</th>
            <td>
                <span class="color-preview" style="background-color: {{ $secondaryColor }};"></span>
                <code>{{ $secondaryColor }}</code>
            </td>
        </tr>
        @if($details && $details->slogan)
            <tr>
                <th>Slogan / Devise</th>
                <td><em>« {{ $details->slogan }} »</em></td>
            </tr>
        @endif
    </table>

    <div class="section-title">Adresse Principale</div>
    <div class="info-card">
        @if($address)
            <p style="margin: 0; color: #4b5563; font-size: 14px;">
                <strong>Rue/Détails :</strong> {{ $address->street ?: 'Non précisé' }}<br>
                <strong>Commune :</strong> {{ $address->commune ?: 'Non précisé' }}<br>
                <strong>Ville / Département :</strong> {{ $address->city ?: 'Non précisé' }} @if($address->department) ({{ $address->department }}) @endif<br>
                <strong>Région / Pays :</strong> {{ $address->region ?: 'Non précisé' }}, {{ $address->country ?: 'Sénégal' }}
            </p>
        @else
            <p style="margin: 0; color: #9ca3af; font-style: italic; font-size: 14px;">Aucune adresse enregistrée.</p>
        @endif
    </div>

    <div class="section-title">Coordonnées Téléphoniques</div>
    @if($school->phones->isNotEmpty())
        <table class="details-table">
            <thead>
                <tr style="background-color: #f3f4f6;">
                    <th style="font-weight: 600; width: 50%;">Type</th>
                    <th style="font-weight: 600; width: 50%;">Numéro</th>
                </tr>
            </thead>
            <tbody>
                @foreach($school->phones as $phone)
                    <tr>
                        <td>
                            <strong>
                                @switch($phone->type->value)
                                    @case('MOBILE')
                                        Mobile
                                        @break
                                    @case('LANDLINE')
                                        Fixe
                                        @break
                                    @case('WHATSAPP')
                                        WhatsApp
                                        @break
                                    @case('EMERGENCY')
                                        Urgence
                                        @break
                                    @default
                                        {{ $phone->type->value }}
                                @endswitch
                            </strong>
                            @if($phone->is_primary)
                                <span class="badge" style="background-color: #d1fae5; color: #065f46; margin-left: 5px; font-size: 10px;">Principal</span>
                            @endif
                        </td>
                        <td>{{ $phone->dial_code }} {{ $phone->phone_number }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="info-card">
            <p style="margin: 0; color: #9ca3af; font-style: italic; font-size: 14px;">Aucun numéro de téléphone enregistré.</p>
        </div>
    @endif

    <div class="btn-container">
        <a href="{{ $frontendUrl }}" class="btn" target="_blank">Accéder à mon tableau de bord</a>
    </div>

    <p>Nous vous remercions pour votre confiance et nous réjouissons de vous accompagner dans la gestion quotidienne de votre école.</p>
    <p>L'équipe support Galsen School</p>
@endsection
