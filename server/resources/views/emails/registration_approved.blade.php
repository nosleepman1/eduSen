@extends('emails.layouts.professional')

@section('title', 'Votre école est approuvée !')

@section('content')
    <h2>Félicitations, votre école est approuvée !</h2>
    <p>Bonjour {{ $owner->firstname }} {{ $owner->lastname }},</p>
    <p>Nous avons le plaisir de vous informer que votre demande d'adhésion pour l'établissement <strong>{{ $school->name }}</strong> a été validée avec succès par notre équipe.</p>
    
    <p>Pour finaliser la configuration de votre espace scolaire et commencer à utiliser la plateforme, vous devez définir votre mot de passe d'administrateur en cliquant sur le bouton ci-dessous :</p>
    
    <div class="btn-container">
        <a href="{{ $url }}" class="btn" target="_blank">Définir mon mot de passe</a>
    </div>
    
    <p style="font-size: 14px; color: #6b7280; font-style: italic;">Note : Ce lien est sécurisé et expire dans 24 heures. Si vous n'avez pas demandé cette activation, vous pouvez ignorer cet e-mail.</p>
    
    <p>Une fois votre mot de passe enregistré, vous serez guidé(e) dans les étapes de configuration de votre école (coordonnées, couleurs, logo, etc.).</p>
    
    <p>Bienvenue dans la communauté Galsen School !</p>
@endsection
