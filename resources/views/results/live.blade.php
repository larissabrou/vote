@extends('layouts.app')

@section('title', 'Résultats en direct - ' . $election->title)

@section('content')
<div class="space-y-6 sm:space-y-8 px-2 sm:px-0 w-full overflow-x-hidden">
    <div class="card p-4 sm:p-6 lg:p-8 text-center">
        <div class="flex items-center justify-center gap-2 mb-2">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                En direct
            </span>
        </div>
        @if($election->logo)
            <img src="{{ $election->logo_url }}" alt="{{ $election->title }}" class="w-20 h-20 sm:w-24 sm:h-24 mx-auto rounded-xl object-cover shadow-lg mb-4">
        @else
            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center shadow-lg mb-4">
                <span class="text-3xl">🗳️</span>
            </div>
        @endif
        <h1 class="text-2xl sm:text-4xl font-extrabold gradient-text mb-2">{{ $election->title }}</h1>
        <p class="text-gray-600">Résultats en temps réel</p>
    </div>

    <div class="p-4 sm:p-6 lg:p-8 rounded-2xl overflow-hidden shadow-2xl relative" style="background: linear-gradient(135deg, #166534 0%, #15803d 50%, #166534 100%);">
        <div class="relative z-10">
            <h2 class="text-xl sm:text-2xl font-bold text-center mb-4 sm:mb-6 text-white">Statistiques</h2>
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">
                <div class="text-center">
                    <div class="text-xs font-semibold uppercase tracking-wider mb-2 text-green-100">Inscrits</div>
                    <div class="text-2xl sm:text-4xl font-extrabold text-white" id="total-voters-stat">0</div>
                </div>
                <div class="text-center">
                    <div class="text-xs font-semibold uppercase tracking-wider mb-2 text-green-100">Votants</div>
                    <div class="text-2xl sm:text-4xl font-extrabold text-white" id="voted-count-stat">0</div>
                </div>
                <div class="text-center col-span-2 lg:col-span-1">
                    <div class="text-xs font-semibold uppercase tracking-wider mb-2 text-green-100">Participation</div>
                    <div class="text-2xl sm:text-4xl font-extrabold text-white" id="participation-rate-stat">0%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-4 sm:p-6 lg:p-8">
        <h2 class="text-xl sm:text-2xl font-bold gradient-text mb-6">Résultats par candidat</h2>
        <div id="candidates-container" class="space-y-6">
            <div class="text-center text-gray-500 py-8">Chargement...</div>
        </div>
    </div>

    <div class="text-center">
        <a href="{{ route('accueil') }}" class="btn btn-secondary">Retour à l'accueil</a>
    </div>
</div>

@push('scripts')
<script>
const apiUrl = @json($apiUrl);

function formatNumber(num) {
    return num != null ? num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') : '0';
}

function renderCandidates(candidates) {
    const byPosition = {};
    (candidates || []).forEach(c => {
        if (!byPosition[c.position]) byPosition[c.position] = [];
        byPosition[c.position].push(c);
    });

    let html = '';
    Object.keys(byPosition).forEach(position => {
        html += '<div class="border-2 border-gray-200 rounded-2xl overflow-hidden bg-white mb-6">';
        html += '<div class="bg-gradient-to-r from-primary-500 to-primary-600 text-white px-4 py-3">';
        html += '<h3 class="text-lg font-bold text-center">' + position + '</h3></div>';
        html += '<div class="divide-y divide-gray-200">';
        byPosition[position].forEach(c => {
            const photoUrl = c.photo_url || '';
            const photo = photoUrl ? '<img src="' + photoUrl + '" alt="" class="w-14 h-14 rounded-full object-cover shadow border-2 border-gray-200">' : '<div class="w-14 h-14 rounded-full bg-gray-300 flex items-center justify-center"><span class="text-xl">👤</span></div>';
            html += '<div class="p-4 flex flex-col sm:flex-row sm:items-center gap-3" data-candidate-id="' + c.id + '">';
            html += '<div class="flex items-center gap-3 flex-1">' + photo + '<div><h4 class="font-bold text-gray-900">' + c.name + '</h4></div></div>';
            html += '<div class="flex items-center gap-4">';
            const totalVoix = c.total_voices != null ? c.total_voices : 0;
            const voixText = totalVoix > 0 ? (formatNumber(c.votes) + ' / ' + formatNumber(totalVoix) + ' voix') : (formatNumber(c.votes) + ' voix');
            html += '<div class="text-center"><div class="text-xl font-extrabold text-primary-600 stat-value">' + voixText + '</div></div>';
            html += '<div class="text-lg font-bold stat-percentage">' + (c.percentage != null ? c.percentage.toFixed(1) : '0') + '%</div>';
            html += '<div class="w-24 sm:w-32 h-5 bg-gray-200 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-emerald-500 to-green-500 vote-bar-fill" style="width:' + (c.percentage || 0) + '%"></div></div>';
            html += '</div></div>';
        });
        html += '</div></div>';
    });
    document.getElementById('candidates-container').innerHTML = html || '<p class="text-gray-500 text-center py-6">Aucun candidat.</p>';
}

function updateStats(data) {
    const total = document.getElementById('total-voters-stat');
    const voted = document.getElementById('voted-count-stat');
    const rate = document.getElementById('participation-rate-stat');
    if (total) total.textContent = formatNumber(data.total_voters);
    if (voted) voted.textContent = formatNumber(data.voted_count);
    if (rate) rate.textContent = (data.progress != null ? data.progress.toFixed(2) : '0') + '%';
}

function fetchResults() {
    fetch(apiUrl)
        .then(r => r.json())
        .then(data => {
            updateStats(data);
            renderCandidates(data.candidates);
        })
        .catch(() => {
            document.getElementById('candidates-container').innerHTML = '<p class="text-center text-red-600 py-6">Erreur de chargement des résultats.</p>';
        });
}

fetchResults();
setInterval(fetchResults, 3000);
</script>
@endpush
@endsection
