@extends('layouts.app')

@section('title', 'Dashboard - Liste des Élections')

@section('content')
<div class="space-y-6 sm:space-y-8 px-2 sm:px-0">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl sm:text-4xl md:text-5xl font-extrabold gradient-text mb-2">Dashboard</h1>
            <p class="text-sm sm:text-base text-gray-600">Liste de toutes les élections enregistrées</p>
        </div>
        <a href="{{ route('elections.creer') }}" class="btn btn-primary group">
            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Créer une élection
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        @forelse($elections as $election)
            <div class="card overflow-hidden group hover:scale-[1.02] transition-all duration-300 min-w-0">
                @if($election->logo)
                    <div class="relative h-40 sm:h-48 overflow-hidden">
                        <img src="{{ $election->logo_url }}" alt="{{ $election->title }}" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                        <div class="absolute top-4 right-4">
                            <span class="status-badge status-{{ $election->status }}">
                                @if($election->status === 'pending') En attente
                                @elseif($election->status === 'active') En cours
                                @else Terminée
                                @endif
                            </span>
                        </div>
                    </div>
                @else
                    <div class="h-48 bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center relative">
                        <span class="text-6xl">🗳️</span>
                        <div class="absolute top-4 right-4">
                            <span class="status-badge status-{{ $election->status }}">
                                @if($election->status === 'pending') En attente
                                @elseif($election->status === 'active') En cours
                                @else Terminée
                                @endif
                            </span>
                        </div>
                    </div>
                @endif
                
                <div class="p-4 sm:p-6">
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-3 sm:mb-4 break-words">{{ Str::limit($election->title, 50) }}</h3>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="font-medium">{{ $election->date_range_display }}</span>
                        </div>
                        
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-5 h-5 text-[#93c46a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ $election->date_time_range_display }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <span class="font-semibold text-gray-700">{{ $election->candidates->count() }}</span>
                                <span class="text-gray-500">candidats</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <span class="font-semibold text-gray-700">{{ $election->voters->count() }}</span>
                                <span class="text-gray-500">votants</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <a href="{{ route('elections.voir', $election) }}" class="btn btn-sm btn-primary flex-1 text-center">
                            Voir les détails
                        </a>
                        <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('votants.inscription', $election) }}" target="_blank" class="btn btn-sm btn-secondary text-center">
                            Lien inscription
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="card p-16 text-center">
                    <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Aucune élection</h3>
                    <p class="text-gray-600 mb-6">Commencez par créer votre première élection</p>
                    <a href="{{ route('elections.creer') }}" class="btn btn-primary">
                        Créer une élection
                    </a>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection

