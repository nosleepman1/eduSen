@extends('emails.layouts.professional')

@section('title', 'Nouvelle demande d\'inscription')

@section('content')
    <h2>Nouvelle Demande d'Inscription d'École</h2>
    <p>Bonjour,</p>
    <p>Une nouvelle demande d'inscription d'établissement scolaire a été soumise sur la plateforme <strong>Galsen School</strong> et est en attente de traitement.</p>
    
    <table class="details-table">
        <tr>
            <th>Nom de l'école</th>
            <td>{{ $request->school_name }}</td>
        </tr>
        <tr>
            <th>Email école</th>
            <td>{{ $request->school_email }}</td>
        </tr>
        <tr>
            <th>NINEA</th>
            <td>{{ $request->ninea }}</td>
        </tr>
        <tr>
            <th>Contact Responsable</th>
            <td>{{ $request->firstname }} {{ $request->lastname }}</td>
        </tr>
        <tr>
            <th>Email Responsable</th>
            <td>{{ $request->email }}</td>
        </tr>
        <tr>
            <th>Téléphone</th>
            <td>{{ $request->phone }}</td>
        </tr>
        <tr>
            <th>Date de soumission</th>
            <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
        </tr>
    </table>
    
    <p>Vous pouvez vous connecter à la plateforme SuperAdmin afin de valider ou rejeter cette demande d'inscription.</p>
@endsection
