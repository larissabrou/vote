@extends('layouts.app')

@section('title', 'Accueil')

@section('content')
<div class="min-h-[calc(100vh-3.5rem)] flex items-center justify-center px-6 py-12">
    <div class="w-full max-w-3xl mx-auto text-center space-y-8">
        <!-- Logo / Icône -->
        <div class="flex justify-center">
            <div class="w-24 h-24 sm:w-32 sm:h-32 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center shadow-xl">
                <span class="text-6xl sm:text-7xl">🗳️</span>
            </div>
        </div>

        <!-- Titre et sous-titre -->
        <div class="space-y-4">
            <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold text-gray-900 tracking-tight leading-tight">
                Système de vote électronique
            </h1>
            <p class="text-gray-600 text-lg sm:text-xl max-w-xl mx-auto">
                Plateforme sécurisée pour la gestion et le suivi en temps réel de vos élections.
            </p>
        </div>

        <div class="flex flex-wrap gap-3 sm:gap-4 justify-center">
            <a href="{{ route('guide-vote') }}" class="btn btn-primary inline-flex items-center gap-2">
                Voir le guide du vote
            </a>
        </div>
    </div>
</div>
@endsection
