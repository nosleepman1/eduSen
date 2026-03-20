@extends('emails.layouts.professional')

@section('title', $title)

@section('content')
    <h2>{{ $title }}</h2>
    <p>Bonjour,</p>
    <p>{!! $introMessage !!}</p>

    @if(!empty($details))
        <table class="details-table">
            @foreach($details as $label => $value)
                <tr>
                    <th>{{ $label }}</th>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <p>L'équipe EduSen</p>
@endsection
