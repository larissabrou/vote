@extends('layouts.app')

@section('title', 'Mes élections')

@php
    // Données dérivées (aucune requête supplémentaire : les relations sont déjà chargées)
    $rows = $elections->map(function ($e) {
        $total = $e->voters->count();
        $voted = $e->voters->where('has_voted', true)->count();
        $unpaid = !$e->isActivated() && !$e->hasEnded();
        return (object) [
            'model'   => $e,
            'total'   => $total,
            'voted'   => $voted,
            'rate'    => $total > 0 ? (int) round($voted / $total * 100) : 0,
            'unpaid'  => $unpaid,
            'status'  => $e->status,
        ];
    });
    $count = [
        'all'       => $rows->count(),
        'active'    => $rows->where('status', 'active')->count(),
        'pending'   => $rows->where('status', 'pending')->count(),
        'completed' => $rows->where('status', 'completed')->count(),
        'unpaid'    => $rows->where('unpaid', true)->count(),
    ];
    $totalVoters = $rows->sum('total');
    $statusLabel = ['pending' => 'À venir', 'active' => 'En cours', 'completed' => 'Terminée'];
    $statusStyle = [
        'pending'   => 'bg-amber-100 text-amber-800 ring-amber-200',
        'active'    => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'completed' => 'bg-gray-100 text-gray-700 ring-gray-200',
    ];
    $statusDot = ['pending' => 'bg-amber-500', 'active' => 'bg-emerald-500 animate-pulse', 'completed' => 'bg-gray-400'];
@endphp

@section('content')
<div class="space-y-6 sm:space-y-8">

    {{-- En-tête --}}
    <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs sm:text-sm font-semibold tracking-wider uppercase text-primary-600 mb-1">Espace administration</p>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Mes élections</h1>
            <p class="mt-1 text-sm sm:text-base text-gray-600">
                @if($count['all'] > 0)
                    {{ $count['all'] }} {{ \Illuminate\Support\Str::plural('élection', $count['all']) }}
                    <span class="mx-1.5 text-gray-300">•</span>
                    {{ number_format($totalVoters, 0, ',', ' ') }} votant{{ $totalVoters > 1 ? 's' : '' }} inscrit{{ $totalVoters > 1 ? 's' : '' }}
                @else
                    Créez et suivez toutes vos élections au même endroit.
                @endif
            </p>
        </div>
        <a href="{{ route('elections.creer') }}" class="btn btn-primary group w-full sm:w-auto">
            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Créer une élection
        </a>
    </header>

    @if($count['all'] > 0)
        {{-- Alerte paiement --}}
        @if($count['unpaid'] > 0)
            <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
                </svg>
                <p class="flex-1">
                    <strong>{{ $count['unpaid'] }} {{ $count['unpaid'] > 1 ? 'élections attendent' : 'élection attend' }} son activation.</strong>
                    Les liens de vote ne sont envoyés aux votants qu'après le paiement.
                </p>
                <button type="button" data-filter="unpaid" class="font-semibold underline underline-offset-2 hover:text-amber-700 whitespace-nowrap">Voir</button>
            </div>
        @endif

        {{-- Filtres + recherche --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <div class="pill-row flex gap-2 overflow-x-auto pb-1 -mx-1 px-1" role="group" aria-label="Filtrer par statut">
                @foreach([
                    'all' => 'Toutes',
                    'active' => 'En cours',
                    'pending' => 'À venir',
                    'completed' => 'Terminées',
                    'unpaid' => 'À activer',
                ] as $key => $label)
                    @if($key === 'all' || $count[$key] > 0)
                        <button type="button" data-filter="{{ $key }}" aria-pressed="{{ $key === 'all' ? 'true' : 'false' }}"
                                class="filter-pill flex-shrink-0 inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition-colors min-h-[40px]">
                            {{ $label }}
                            <span class="filter-count rounded-full px-2 py-0.5 text-xs">{{ $count[$key] }}</span>
                        </button>
                    @endif
                @endforeach
            </div>
            <div class="relative w-full lg:w-72">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"></path>
                </svg>
                <input id="election-search" type="search" placeholder="Rechercher une élection…" autocomplete="off" aria-label="Rechercher une élection"
                       class="w-full rounded-xl border-2 border-gray-200 bg-white py-2.5 pl-11 pr-4 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
        </div>

        {{-- Grille --}}
        <div id="election-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 sm:gap-6">
            @foreach($rows as $row)
                @php $election = $row->model; @endphp
                <article class="election-card group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-md transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                         data-status="{{ $row->status }}" data-unpaid="{{ $row->unpaid ? '1' : '0' }}"
                         data-title="{{ \Illuminate\Support\Str::lower($election->title) }}">

                    {{-- Bannière --}}
                    <div class="relative h-36 overflow-hidden bg-gradient-to-br from-primary-500 to-primary-700">
                        @if($election->logo)
                            <img src="{{ $election->logo_url }}" alt="" loading="lazy"
                                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
                        @else
                            <div class="absolute inset-0 flex items-center justify-center text-white/90">
                                <svg class="w-14 h-14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"></path>
                                </svg>
                            </div>
                        @endif
                        <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $statusStyle[$row->status] ?? $statusStyle['completed'] }}">
                            <span class="h-2 w-2 rounded-full {{ $statusDot[$row->status] ?? $statusDot['completed'] }}"></span>
                            {{ $statusLabel[$row->status] ?? 'Terminée' }}
                        </span>
                        @if($row->unpaid)
                            <span class="absolute right-3 top-3 inline-flex items-center rounded-full bg-amber-500 px-3 py-1 text-xs font-bold text-white shadow">À activer</span>
                        @endif
                    </div>

                    {{-- Contenu --}}
                    <div class="flex flex-1 flex-col p-5">
                        <h2 class="mb-3 text-lg font-bold leading-snug text-gray-900 line-clamp-2" title="{{ $election->title }}">{{ $election->title }}</h2>

                        <dl class="space-y-2 text-sm text-gray-600">
                            <div class="flex items-center gap-2.5">
                                <dt class="sr-only">Dates</dt>
                                <svg class="w-4 h-4 flex-shrink-0 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <dd class="font-medium text-gray-700">{{ $election->date_range_display }}</dd>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <dt class="sr-only">Horaires</dt>
                                <svg class="w-4 h-4 flex-shrink-0 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <dd>{{ $election->date_time_range_display }}</dd>
                            </div>
                        </dl>

                        {{-- Participation --}}
                        <div class="mt-4">
                            <div class="mb-1.5 flex items-baseline justify-between text-xs">
                                <span class="font-semibold uppercase tracking-wide text-gray-500">Participation</span>
                                @if($row->total > 0)
                                    <span class="font-semibold text-gray-700">{{ $row->voted }}/{{ $row->total }} <span class="text-gray-400">·</span> {{ $row->rate }}%</span>
                                @else
                                    <span class="text-gray-400">Aucun votant</span>
                                @endif
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $row->rate }}">
                                <div class="h-full rounded-full bg-gradient-to-r from-primary-400 to-primary-600" style="width: {{ $row->rate }}%"></div>
                            </div>
                        </div>

                        {{-- Compteurs --}}
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-gray-50 px-3 py-2.5 text-center">
                                <div class="text-xl font-bold text-gray-900">{{ $election->candidates->count() }}</div>
                                <div class="text-xs text-gray-500">{{ $election->candidates->count() > 1 ? 'Candidats' : 'Candidat' }}</div>
                            </div>
                            <div class="rounded-xl bg-gray-50 px-3 py-2.5 text-center">
                                <div class="text-xl font-bold text-gray-900">{{ $row->total }}</div>
                                <div class="text-xs text-gray-500">{{ $row->total > 1 ? 'Votants' : 'Votant' }}</div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="mt-5 space-y-2.5 pt-1">
                            @if($row->unpaid)
                                <a href="{{ route('paiement.election.activer', $election) }}"
                                   class="btn btn-sm w-full bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-500">
                                    Activer · {{ number_format(\App\Models\Payment::AMOUNT_ELECTION_FCFA, 0, ',', ' ') }} FCFA
                                </a>
                            @endif
                            <div class="flex gap-2.5">
                                <a href="{{ route('elections.voir', $election) }}"
                                   class="btn btn-sm flex-1 {{ $row->unpaid ? 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 focus:ring-gray-300' : 'btn-primary' }}">
                                    Voir les détails
                                </a>
                                @if($election->isEditable())
                                    <a href="{{ route('elections.modifier', $election) }}"
                                       class="btn btn-sm border-gray-200 bg-white text-gray-700 hover:bg-gray-50 focus:ring-gray-300" aria-label="Modifier {{ $election->title }}">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.4-9.4a2 2 0 112.8 2.8L11.8 15H9v-2.8l8.6-8.6z"></path>
                                        </svg>
                                        Modifier
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Aucun résultat pour le filtre / la recherche --}}
        <div id="no-results" class="hidden rounded-2xl border border-dashed border-gray-300 bg-white/70 p-10 text-center">
            <p class="text-lg font-semibold text-gray-800">Aucune élection ne correspond</p>
            <p class="mt-1 text-sm text-gray-500">Modifiez la recherche ou le filtre.</p>
            <button type="button" id="reset-filters" class="mt-4 text-sm font-semibold text-primary-600 hover:text-primary-800 underline underline-offset-2">Réinitialiser</button>
        </div>
    @else
        {{-- Aucune élection --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-10 sm:p-16 text-center shadow-lg">
            <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary-50">
                <svg class="h-10 w-10 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Aucune élection pour le moment</h2>
            <p class="mx-auto mt-2 max-w-md text-gray-600">Créez votre première élection, ajoutez vos candidats puis importez la liste des votants.</p>
            <a href="{{ route('elections.creer') }}" class="btn btn-primary mt-6">Créer une élection</a>
        </div>
    @endif
</div>

@push('styles')
<style>
    .pill-row { scrollbar-width: none; }
    .pill-row::-webkit-scrollbar { display: none; }
    .filter-pill { border-color: #e5e7eb; background: #fff; color: #374151; }
    .filter-pill .filter-count { background: #f3f4f6; color: #6b7280; }
    .filter-pill:hover { border-color: #00bf63; color: #008f47; }
    .filter-pill[aria-pressed="true"] { background: #00bf63; border-color: #00bf63; color: #fff; }
    .filter-pill[aria-pressed="true"] .filter-count { background: rgba(255,255,255,.25); color: #fff; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var cards = Array.prototype.slice.call(document.querySelectorAll('.election-card'));
    if (!cards.length) return;
    var pills = Array.prototype.slice.call(document.querySelectorAll('.filter-pill'));
    var search = document.getElementById('election-search');
    var empty = document.getElementById('no-results');
    var state = { filter: 'all', q: '' };

    function norm(s) { return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }

    function apply() {
        var shown = 0;
        cards.forEach(function (c) {
            var okFilter = state.filter === 'all'
                || (state.filter === 'unpaid' ? c.dataset.unpaid === '1' : c.dataset.status === state.filter);
            var okSearch = !state.q || norm(c.dataset.title).indexOf(state.q) !== -1;
            var show = okFilter && okSearch;
            c.classList.toggle('hidden', !show);
            if (show) shown++;
        });
        pills.forEach(function (p) { p.setAttribute('aria-pressed', p.dataset.filter === state.filter ? 'true' : 'false'); });
        empty.classList.toggle('hidden', shown > 0);
    }

    document.querySelectorAll('[data-filter]').forEach(function (el) {
        el.addEventListener('click', function () { state.filter = el.dataset.filter; apply(); });
    });
    if (search) search.addEventListener('input', function () { state.q = norm(search.value.trim()); apply(); });
    var reset = document.getElementById('reset-filters');
    if (reset) reset.addEventListener('click', function () { state = { filter: 'all', q: '' }; if (search) search.value = ''; apply(); });
})();
</script>
@endpush
@endsection
