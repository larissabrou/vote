@extends('layouts.app')

@section('title', 'Mot de passe oublié')

@section('content')
<div class="min-h-[calc(100vh-3.5rem)] sm:min-h-[calc(100vh-5rem)] flex items-center justify-center py-6 sm:py-12 px-3 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6 sm:space-y-8 mx-auto">
        <div class="card p-5 sm:p-8">
            <div class="text-center mb-6 sm:mb-8">
                <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-4 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center shadow-xl">
                    <svg class="w-8 h-8 sm:w-10 sm:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold gradient-text">Mot de passe oublié</h2>
                <p class="mt-2 text-sm text-gray-600">Indiquez votre email pour recevoir un lien de réinitialisation</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success mb-6">
                    {{ session('status') }}
                </div>
            @endif

            <form class="space-y-6" action="{{ route('administration.mot-de-passe.email') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="email">Adresse email</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary w-full">
                        Envoyer le lien de réinitialisation
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center space-y-2">
                <a href="{{ route('administration.connexion') }}" class="text-sm text-primary-600 hover:text-primary-800 font-medium">Retour à la connexion</a>
                <span class="mx-2">·</span>
                <a href="{{ route('accueil') }}" class="text-sm text-primary-600 hover:text-primary-800 font-medium">Retour à l'accueil</a>
            </div>
        </div>
    </div>
</div>
@endsection
