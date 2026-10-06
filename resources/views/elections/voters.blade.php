@extends('layouts.app')

@section('title', 'Liste des votants - ' . $election->title)

@section('content')
<div class="space-y-8">
    <!-- Header Section -->
    <div class="card p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">{{ $election->title }}</h1>
                <p class="text-gray-600">Liste des votants et nombre de voix</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('elections.electeurs.export', $election) }}" class="btn btn-primary whitespace-nowrap">
                    <svg class="w-5 h-5 mr-2 inline-block align-middle" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Exporter Excel
                </a>
                <a href="{{ route('elections.voir', $election) }}" class="btn btn-secondary group">
                    <svg class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Retour
                </a>
            </div>
        </div>
        <div class="rounded-xl border border-primary-200 bg-primary-50 p-4">
            <p class="text-sm text-primary-800 mb-2 font-medium">Lien public d'inscription des votants :</p>
            <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                <input type="text" readonly value="{{ \Illuminate\Support\Facades\URL::signedRoute('votants.inscription', $election) }}" class="form-control text-sm">
                <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('votants.inscription', $election) }}" target="_blank" class="btn btn-primary whitespace-nowrap">Ouvrir</a>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    @if($voters->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl p-8 shadow-xl border-2 border-primary-400 transform hover:scale-105 transition-transform duration-300">
                <div class="text-sm font-bold text-white/90 uppercase tracking-wider mb-3">TOTAL VOTANTS</div>
                <div class="text-5xl font-extrabold text-white">{{ $voters->count() }}</div>
            </div>
            <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl p-8 shadow-xl border-2 border-green-400 transform hover:scale-105 transition-transform duration-300">
                <div class="text-sm font-bold text-white/90 uppercase tracking-wider mb-3">ONT VOTÉ</div>
                <div class="text-5xl font-extrabold text-white">{{ $voters->where('has_voted', true)->count() }}</div>
            </div>
            <div class="bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl p-8 shadow-xl border-2 border-primary-400 transform hover:scale-105 transition-transform duration-300">
                <div class="text-sm font-bold text-white/90 uppercase tracking-wider mb-3">TOTAL Bulletins</div>
                <div class="text-5xl font-extrabold text-white">{{ $voters->sum('vote_count') }}</div>
            </div>
        </div>
    @endif

    <!-- Renvoyer les liens (si élection activée et des envois en échec) -->
    @php
        $isActivated = $election->isActivated();
        $votersFailedEmail = $voters->filter(fn($v) => !empty($v['email_error']))->count();
        $votersPendingEmail = $voters->filter(fn($v) => empty($v['email_sent_at']) && empty($v['email_error']))->count();
    @endphp
    @if($isActivated && ($votersFailedEmail > 0 || $votersPendingEmail > 0))
    <div class="card p-6 border-2 border-amber-200 bg-amber-50">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-amber-900 font-medium">
                @if($votersFailedEmail > 0)
                    <strong>{{ $votersFailedEmail }}</strong> votant(s) en echec et <strong>{{ $votersPendingEmail }}</strong> en attente d'envoi.
                @else
                    <strong>{{ $votersPendingEmail }}</strong> votant(s) en attente d'envoi de lien.
                @endif
            </p>
            <form action="{{ route('elections.renvoyer-liens', $election) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn bg-amber-500 hover:bg-amber-600 text-white">
                    Envoyer/Renvoyer les liens en masse
                </button>
            </form>
        </div>
    </div>
    @endif
    @if($isActivated && $votersPendingEmail > 0)
    <div class="card p-6 border-2 border-blue-200 bg-blue-50">
        <p class="text-blue-900 font-medium">
            <strong>{{ $votersPendingEmail }}</strong> votant(s) sont en attente d'envoi de lien (pas encore tentés).
        </p>
    </div>
    @endif

    @if(!$isActivated)
    <div class="card p-6 border-2 border-primary-200 bg-primary-50">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-primary-900 font-medium">
                Quand les votants ont fini de s'inscrire, cliquez pour envoyer les liens de vote a toute la liste.
            </p>
            <a href="{{ route('paiement.election.activer', $election) }}" class="btn btn-primary">
                Envoyer les liens aux votants
            </a>
        </div>
    </div>
    @endif

    <!-- Liste des votants -->
    <div class="card p-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold gradient-text">Liste des votants</h2>
            <p class="text-gray-600 mt-2">Total : {{ $voters->count() }} votant(s)</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom complet</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre de voix</th>
                        @if($isActivated)
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Lien envoyé</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        @endif
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de vote</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($voters as $index => $voter)
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">{{ $voter['full_name'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="voter-email-cell text-sm text-gray-600" data-voter-id="{{ $voter['id'] }}">
                                    <span class="email-display">{{ $voter['email'] }}</span>
                                    @if(empty($voter['email_sent_at']) && !empty($voter['email_error']))
                                    <form class="email-edit-form hidden mt-2 flex flex-wrap items-center gap-2" method="POST" action="{{ route('elections.voters.mettre-a-jour', [$election, $voter['id']]) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="email" name="email" value="{{ $voter['email'] }}" class="form-control text-sm w-48 max-w-full" required>
                                        <button type="submit" class="btn btn-primary btn-sm">Enregistrer</button>
                                        <button type="button" class="btn btn-secondary btn-sm cancel-edit-email">Annuler</button>
                                    </form>
                                    <button type="button" class="ml-1 text-primary-600 hover:underline text-xs font-medium toggle-edit-email">Modifier l'email</button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if(!$isActivated && !$voter['has_voted'])
                                    <div class="flex flex-col items-center gap-2">
                                        <form method="POST" action="{{ route('elections.voters.mettre-a-jour-voix', [$election, $voter['id']]) }}" class="inline-flex items-center gap-2 justify-center">
                                            @csrf
                                            @method('PUT')
                                            <input type="number" name="vote_count" min="1" max="100" value="{{ $voter['vote_count'] }}" class="form-control text-sm w-20 text-center" required>
                                            <button type="submit" class="btn btn-primary btn-sm">OK</button>
                                        </form>
                                        <form method="POST" action="{{ route('elections.voters.supprimer', [$election, $voter['id']]) }}" class="inline" data-confirm-message="Supprimer ce votant ?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-text-white" style="background-color:#dc2626 !important;color:#ffffff !important;">Supprimer</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-primary-100 text-primary-700">
                                        {{ $voter['vote_count'] }} voix
                                    </span>
                                @endif
                            </td>
                            @if($isActivated)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($voter['email_sent_at'])
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800" title="Envoyé le {{ \Carbon\Carbon::parse($voter['email_sent_at'])->format('d/m/Y H:i') }}">
                                        Envoyé
                                    </span>
                                @elseif(!empty($voter['email_error']))
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800" title="{{ $voter['email_error'] }}">
                                        Échec
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800" title="Envoi pas encore tenté">
                                        En attente
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('elections.voters.renvoyer-lien', [$election, $voter['id']]) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm bg-amber-500 hover:bg-amber-600 text-white">Renvoyer le lien</button>
                                    </form>
                                    @if(!$voter['has_voted'])
                                    <form method="POST" action="{{ route('elections.voters.supprimer', [$election, $voter['id']]) }}" class="inline" data-confirm-message="Supprimer ce votant ?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-text-white" style="background-color:#dc2626 !important;color:#ffffff !important;">Supprimer</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($voter['has_voted'])
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        A voté
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                        N'a pas voté
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                @if($voter['voted_at'])
                                    {{ \Carbon\Carbon::parse($voter['voted_at'])->format('d/m/Y H:i') }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isActivated ? 8 : 6 }}" class="px-6 py-12 text-center text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <p class="text-lg">Aucun votant enregistré pour cette élection.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.toggle-edit-email').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var cell = this.closest('.voter-email-cell');
        if (!cell) return;
        cell.querySelector('.email-display').classList.add('hidden');
        cell.querySelector('.email-edit-form').classList.remove('hidden');
        cell.querySelector('.toggle-edit-email').classList.add('hidden');
    });
});
document.querySelectorAll('.cancel-edit-email').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var cell = this.closest('.voter-email-cell');
        if (!cell) return;
        cell.querySelector('.email-display').classList.remove('hidden');
        cell.querySelector('.email-edit-form').classList.add('hidden');
        cell.querySelector('.toggle-edit-email').classList.remove('hidden');
    });
});
</script>
@endpush
@endsection

