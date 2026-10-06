<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lien de vote - {{ $election->title }}</title>
</head>
<body>
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #333;">Bonjour {{ $voter->full_name }},</h1>
        
        <p>Vous êtes invité(e) à participer à l'élection : <strong>{{ $election->title }}</strong></p>
        
        <p><strong>Date(s) et horaire:</strong> {{ $election->date_time_range_display }}</p>
        <p><strong>Nombre de voix:</strong> {{ $voter->vote_count }}</p>
        
        <div style="margin: 30px 0; text-align: center;">
            <a href="{{ $voteUrl }}" 
               style="background-color: #4CAF50; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Accéder au vote
            </a>
        </div>
        
        <p style="color: #666; font-size: 12px;">
            Ce lien est unique et personnel. Ne le partagez pas avec d'autres personnes.
        </p>
        
        @if(!empty($resultsUrl))
        <p style="color: #666; font-size: 14px; margin-top: 24px;">
            <strong>Après avoir voté</strong>, vous pourrez consulter les résultats en direct avec ce lien unique (à conserver) :
        </p>
        <p style="margin: 12px 0;">
            <a href="{{ $resultsUrl }}" style="color: #15803d; text-decoration: underline;">{{ $resultsUrl }}</a>
        </p>
        @endif
        
        <p style="color: #666; font-size: 12px;">
            Si vous ne pouvez pas cliquer sur le bouton, copiez et collez ce lien dans votre navigateur:<br>
            {{ $voteUrl }}
        </p>
    </div>
</body>
</html>

