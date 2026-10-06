@extends('layouts.app')

@section('title', 'Créer un compte')

@section('content')
<div class="min-h-[calc(100vh-3.5rem)] sm:min-h-[calc(100vh-5rem)] flex items-center justify-center py-6 sm:py-12 px-3 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6 sm:space-y-8 mx-auto">
        <div class="card p-5 sm:p-8">
            <div class="text-center mb-6 sm:mb-8">
                <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-4 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center shadow-xl">
                    <svg class="w-8 h-8 sm:w-10 sm:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold gradient-text">Créer un compte</h2>
                <p class="mt-2 text-sm text-gray-600">Inscrivez-vous pour organiser des élections</p>
            </div>

            <form class="space-y-6" action="{{ route('administration.inscription.post') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="name">Nom complet</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                    <label for="email">Adresse email</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" name="password" id="password" class="form-control" required minlength="8">
                    <small class="text-gray-500">Minimum 8 caractères</small>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary w-full">
                        Créer mon compte
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('administration.connexion') }}" class="text-sm text-primary-600 hover:text-primary-800 font-medium">Déjà un compte ? Se connecter</a>
            </div>
        </div>
    </div>
</div>
@endsection
