@extends('layouts.app')

@section('title', $data['election']->title)

@section('content')
<div class="space-y-6 sm:space-y-8 px-2 sm:px-0 w-full overflow-x-hidden">
    <!-- Header Section -->
    <div class="card p-4 sm:p-6 lg:p-8 text-center">
        <div class="space-y-4 sm:space-y-6">
            @if($data['election']->logo)
                <div class="inline-block">
                    <img src="{{ $data['election']->logo_url }}" alt="{{ $data['election']->title }}" 
                         class="w-24 h-24 sm:w-32 sm:h-32 mx-auto rounded-2xl object-cover shadow-2xl border-4 border-white">
                </div>
            @else
                <div class="inline-block">
                    <div class="w-24 h-24 sm:w-32 sm:h-32 mx-auto bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center shadow-2xl">
                        <span class="text-4xl sm:text-5xl">🗳️</span>
                    </div>
                </div>
            @endif
            
            <div>
                <h1 class="text-2xl sm:text-4xl md:text-5xl font-extrabold gradient-text mb-4 break-words">{{ $data['election']->title }}</h1>
                <div class="flex flex-wrap justify-center gap-3 sm:gap-6 text-gray-600 text-sm sm:text-base">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="font-semibold text-gray-800">{{ $data['election']->date_range_display }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#93c46a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ $data['election']->date_time_range_display }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="status-badge status-{{ $data['election']->status }}">
                            @if($data['election']->status === 'pending') En attente
                            @elseif($data['election']->status === 'active') En cours
                            @else Terminée
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @if(!$data['election']->isActivated())
            <div class="mt-4 p-4 rounded-xl text-center {{ $data['election']->hasEnded() ? 'bg-gray-100 border-2 border-gray-200' : 'bg-amber-50 border-2 border-amber-200' }}">
                @if($data['election']->hasEnded())
                    <p class="text-gray-700 font-medium mb-3">La date ou l'heure de l'élection est passée. Modifiez la date et/ou l'heure de l'élection pour pouvoir payer et activer.</p>
                    <a href="{{ route('elections.modifier', $data['election']) }}" class="btn bg-gray-600 hover:bg-gray-700 text-white btn-text-white">Modifier l'élection</a>
                @else
                    <p class="text-amber-800 font-medium mb-3">Cette élection n'est pas encore activée. Les votants n'ont pas reçu leurs liens de vote.</p>
                    <a href="{{ route('paiement.election.activer', $data['election']) }}" class="btn bg-amber-500 hover:bg-amber-600 text-white">
                        Activer l'élection ({{ number_format(\App\Models\Payment::AMOUNT_ELECTION_FCFA, 0, ',', ' ') }} FCFA) — Envoyer les emails aux votants
                    </a>
                @endif
            </div>
        @endif
        
        <!-- Bouton Liste des votants -->
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('elections.electeurs', $data['election']) }}" class="btn btn-primary group">
                <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                Liste des votants
            </a>
            <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('votants.inscription', $data['election']) }}" target="_blank" class="btn btn-secondary">
                Lien d'inscription votant
            </a>
            @if($data['election']->isActivated() && ($data['voters_email_failed_count'] ?? 0) > 0)
                <form action="{{ route('elections.renvoyer-liens', $data['election']) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn bg-amber-500 hover:bg-amber-600 text-white">
                        Renvoyer les liens ({{ $data['voters_email_failed_count'] }} en échec)
                    </button>
                </form>
            @endif
        </div>
        @if($data['election']->isActivated() && ($data['voters_email_pending_count'] ?? 0) > 0)
        <div class="mt-4 p-4 rounded-xl bg-primary-50 border-2 border-primary-200 text-center">
            <p class="text-primary-900 font-medium mb-3">
                <strong>{{ $data['voters_email_pending_count'] }}</strong> votant(s) n’ont pas encore reçu leur lien de vote.
            </p>
            <form action="{{ route('elections.renvoyer-liens', $data['election']) }}" method="POST" class="inline"
                  data-confirm-message="Envoyer le lien de vote à {{ $data['voters_email_pending_count'] }} votant(s) ?">
                @csrf
                <button type="submit" class="btn btn-primary">
                    Envoyer les liens aux votants ({{ $data['voters_email_pending_count'] }})
                </button>
            </form>
        </div>
        @endif
        @if($data['election']->isActivated() && ($data['voters_email_failed_count'] ?? 0) > 0)
        <div class="mt-4 p-4 rounded-xl bg-amber-50 border-2 border-amber-200 text-center">
            <p class="text-amber-800 text-sm">Certains votants n'ont pas reçu leur lien (adresse invalide ou erreur d'envoi). Corrigez les emails dans la liste des votants ou renvoyez après correction côté serveur.</p>
        </div>
        @endif
    </div>

    <!-- Statistiques complètes - Style bulletin officiel -->
    <div class="p-4 sm:p-6 lg:p-8 rounded-2xl overflow-hidden relative shadow-2xl" style="background: linear-gradient(135deg, #166534 0%, #15803d 50%, #166534 100%);">
        <div class="relative z-10">
            <h2 class="text-xl sm:text-3xl font-bold text-center mb-4 sm:mb-8" style="color: #ffffff; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">Statistiques de l'élection</h2>
            
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">
                <!-- INSCRITS -->
                <div class="text-center">
                    <div class="text-sm font-semibold uppercase tracking-wider mb-3" style="color: #dcfce7;">INSCRITS</div>
                    <div class="text-2xl sm:text-4xl md:text-5xl font-extrabold" id="total-voters-stat" style="color: #ffffff; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
                        {{ number_format($data['total_voters'], 0, ',', ' ') }}
                    </div>
                </div>
                
                <!-- VOTANTS -->
                <div class="text-center">
                    <div class="text-sm font-semibold uppercase tracking-wider mb-3" style="color: #dcfce7;">VOTANTS</div>
                    <div class="text-2xl sm:text-4xl md:text-5xl font-extrabold" id="voted-count-stat" style="color: #ffffff; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
                        {{ number_format($data['voted_count'], 0, ',', ' ') }}
                    </div>
                </div>
                
                <!-- TAUX DE PARTICIPATION -->
                <div class="text-center">
                    <div class="text-sm font-semibold uppercase tracking-wider mb-3" style="color: #dcfce7;">TAUX DE PARTICIPATION</div>
                    <div class="text-2xl sm:text-4xl md:text-5xl font-extrabold" id="participation-rate-stat" style="color: #ffffff; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
                        {{ number_format($data['progress'], 2, ',', ' ') }}%
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Results Section - Bulletin de vote style -->
    <div class="card p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 sm:mb-8">
            <h2 class="text-xl sm:text-3xl font-bold gradient-text">Résultats par candidat</h2>
            <div class="px-4 py-2 bg-primary-100 text-primary-700 rounded-full font-bold">
                {{ count($data['candidates']) }} candidats
            </div>
        </div>
        
        <div class="mb-10 bg-gray-50 rounded-2xl p-6">
            <canvas id="results-chart"></canvas>
        </div>
        
        <!-- Groupement par position/titre -->
        @php
            $groupedCandidates = collect($data['candidates'])->groupBy('position');
        @endphp
        
        <div class="space-y-8">
            @foreach($groupedCandidates as $position => $candidates)
                <div class="border-2 border-gray-200 rounded-2xl overflow-hidden bg-white">
                    <!-- En-tête du groupe (titre/position) -->
                    <div class="bg-gradient-to-r from-primary-500 to-primary-600 text-white px-4 sm:px-6 py-3 sm:py-4">
                        <h3 class="text-xl sm:text-2xl font-bold text-center break-words">{{ $position }}</h3>
                        <p class="text-sm text-white/80 text-center mt-1">{{ $candidates->count() }} candidat(s)</p>
                    </div>
                    
                    <!-- Liste verticale des candidats -->
                    <div class="divide-y divide-gray-200">
                        @foreach($candidates as $index => $candidate)
                            <div class="p-4 sm:p-6 hover:bg-gray-50 transition-colors duration-200" data-candidate-id="{{ $candidate['id'] }}">
                                <div class="flex flex-col md:flex-row md:items-center gap-4 sm:gap-6">
                                    <!-- Photo et nom du candidat -->
                                    <div class="flex items-center gap-4 flex-1">
                                        @if($candidate['photo'])
                                            <div class="relative flex-shrink-0">
                                                <img src="{{ asset($candidate['photo']) }}" alt="{{ $candidate['name'] }}"
                                                     class="w-20 h-20 rounded-full object-cover shadow-lg border-2 border-gray-200">
                                            </div>
                                        @else
                                            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 flex items-center justify-center shadow-lg border-2 border-gray-200 flex-shrink-0">
                                                <span class="text-2xl">👤</span>
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-xl font-bold text-gray-900 mb-1">{{ $candidate['name'] }}</h4>
                                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                                <span class="px-2 py-1 bg-primary-100 text-primary-700 rounded font-semibold">
                                                    #{{ $index + 1 }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Résultats (voix obtenues / total des voix possibles pour ce poste) -->
                                    <div class="flex flex-col md:flex-row md:items-center gap-3 sm:gap-4 md:gap-8">
                                        <div class="text-center md:text-right">
                                            @php
                                                $totalVoix = $candidate['total_voices'] ?? 0;
                                                $voixObtenues = $candidate['votes'];
                                            @endphp
                                            <div class="text-2xl sm:text-3xl font-extrabold bg-gradient-to-r from-primary-500 to-primary-600 bg-clip-text text-transparent stat-value" data-candidate-id="{{ $candidate['id'] }}">
                                                @if($totalVoix > 0)
                                                    {{ number_format($voixObtenues, 0, ',', ' ') }} / {{ number_format($totalVoix, 0, ',', ' ') }} voix
                                                @else
                                                    {{ number_format($voixObtenues, 0, ',', ' ') }} voix
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="text-center md:text-right">
                                            <div class="text-xl sm:text-2xl font-bold text-gray-700 stat-percentage" data-candidate-id="{{ $candidate['id'] }}">
                                                {{ number_format($candidate['percentage'], 1) }}%
                                            </div>
                                        </div>
                                        
                                        <!-- Barre de progression -->
                                        <div class="flex items-center gap-3 w-full min-w-0">
                                            <div class="w-full max-w-xs sm:max-w-none sm:w-48 h-6 bg-gray-200 rounded-full overflow-hidden shadow-inner flex-shrink">
                                                <div class="h-full bg-gradient-to-r from-emerald-500 to-green-500 transition-all duration-500 vote-bar-fill flex items-center justify-end pr-2" 
                                                     data-candidate-id="{{ $candidate['id'] }}" 
                                                     style="width: {{ $candidate['percentage'] }}%">
                                                    @if($candidate['percentage'] > 10)
                                                        <span class="text-xs font-bold text-white">{{ number_format($candidate['percentage'], 1) }}%</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 justify-center">
        @if($data['election']->isEditable())
            <a href="{{ route('elections.modifier', $data['election']) }}" class="btn btn-primary group btn-text-white">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Modifier l'élection
            </a>
        @endif
        @if($data['election']->status === 'pending' && !$data['election']->isActivated())
            <form action="{{ route('elections.activer', $data['election']) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn btn-success group">
                    <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Activer l'élection
                </button>
            </form>
        @endif
        <a href="{{ route('elections.liste') }}" class="btn btn-secondary group">
            <svg class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour
        </a>
    </div>
</div>

@push('scripts')
<script src="https://cdn.socket.io/4.6.1/socket.io.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const electionId = {{ $data['election']->id }};
const socket = io('http://localhost:3000');

socket.on('connect', () => {
    console.log('Connecté au serveur Socket.io');
    socket.emit('join-election', electionId);
});

socket.on('vote-updated', (data) => {
    console.log('Mise à jour reçue:', data);
    updateDashboard(data);
});

// Plugin personnalisé pour afficher les valeurs au-dessus des barres
const chartLabelPlugin = {
    id: 'chartLabelPlugin',
    afterDatasetsDraw: (chart) => {
        const ctx = chart.ctx;
        chart.data.datasets.forEach((dataset, i) => {
            const meta = chart.getDatasetMeta(i);
            meta.data.forEach((bar, index) => {
                const value = dataset.data[index];
                const x = bar.x;
                const y = bar.y;
                
                ctx.save();
                ctx.fillStyle = '#374151';
                ctx.font = 'bold 14px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                ctx.fillText(value + ' voix', x, y - 5);
                ctx.restore();
            });
        });
    }
};

// Initialiser le graphique
const ctx = document.getElementById('results-chart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'bar',
    plugins: [chartLabelPlugin],
    data: {
        labels: @json(collect($data['candidates'])->pluck('name')->toArray()),
        datasets: [{
            label: 'Votes',
            data: @json(collect($data['candidates'])->pluck('votes')->toArray()),
            backgroundColor: 'rgba(0, 191, 99, 0.5)',
            borderColor: 'rgba(0, 191, 99, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function updateDashboard(data) {
    // Calculer la progression
    const progress = data.progress || (data.total_voters > 0 ? (data.voted_count / data.total_voters) * 100 : 0);
    
    // Mettre à jour la progression globale (cercle)
    const progressText = document.querySelector('.progress-text');
    if (progressText) {
        progressText.textContent = progress.toFixed(1) + '%';
    }
    
    const progressCircle = document.querySelector('.progress-ring-circle-active');
    if (progressCircle) {
        const radius = 64; // Rayon du cercle
        const circumference = 2 * Math.PI * radius;
        const offset = circumference * (1 - progress / 100);
        progressCircle.setAttribute('stroke-dashoffset', offset);
    }
    
    // Mettre à jour les votants (Ont voté / Total)
    const metricValues = document.querySelectorAll('.metric-value');
    if (metricValues.length > 1) {
        metricValues[1].innerHTML = `${data.voted_count}<span class="text-2xl text-gray-400">/${data.total_voters}</span>`;
    }
    
    // Mettre à jour les bulletins nuls
    const nullVotesElement = document.getElementById('null-votes-count');
    if (nullVotesElement) {
        nullVotesElement.textContent = data.null_votes || 0;
    } else if (metricValues.length > 2) {
        metricValues[2].textContent = data.null_votes || 0;
    }
    
    // Mettre à jour les statistiques officielles
    const formatNumber = (num) => {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    };
    
    const totalVotersStat = document.getElementById('total-voters-stat');
    if (totalVotersStat && data.total_voters !== undefined) {
        totalVotersStat.textContent = formatNumber(data.total_voters);
    }
    
    const votedCountStat = document.getElementById('voted-count-stat');
    if (votedCountStat && data.voted_count !== undefined) {
        votedCountStat.textContent = formatNumber(data.voted_count);
    }
    
    const participationRateStat = document.getElementById('participation-rate-stat');
    if (participationRateStat && progress !== undefined) {
        participationRateStat.textContent = progress.toFixed(2).replace('.', ',') + '%';
    }
    
    // Calculer le nombre total de voix (somme des votes des candidats)
    let totalVoices = data.total_voices;
    if (totalVoices === undefined && data.candidates) {
        totalVoices = data.candidates.reduce((sum, candidate) => sum + (candidate.votes || 0), 0);
    }
    const totalVoicesStat = document.getElementById('total-voices-stat');
    if (totalVoicesStat) {
        totalVoicesStat.textContent = formatNumber(totalVoices || 0);
    }
    
    const nullVotesStat = document.getElementById('null-votes-stat');
    if (nullVotesStat && data.null_votes !== undefined) {
        nullVotesStat.textContent = formatNumber(data.null_votes || 0);
    }
    
    const blankVotesStat = document.getElementById('blank-votes-stat');
    if (blankVotesStat && data.blank_votes !== undefined) {
        blankVotesStat.textContent = formatNumber(data.blank_votes || 0);
    }
    
    // Suffrages exprimés (voix pour des candidats, hors bulletins blancs)
    let validVotes = data.valid_votes;
    if (validVotes === undefined) {
        validVotes = Math.max(0, (totalVoices || 0) - (data.null_votes || 0));
    }
    const validVotesStat = document.getElementById('valid-votes-stat');
    if (validVotesStat) {
        validVotesStat.textContent = formatNumber(validVotes || 0);
    }
    
    // Mettre à jour le graphique
    if (chart && data.candidates) {
        chart.data.labels = data.candidates.map(c => c.name);
        chart.data.datasets[0].data = data.candidates.map(c => c.votes);
        chart.update('none'); // Animation 'none' pour une mise à jour fluide
    }
    
    // Mettre à jour les cartes de candidats
    if (data.candidates) {
        data.candidates.forEach(candidate => {
            const statValue = document.querySelector(`.stat-value[data-candidate-id="${candidate.id}"]`);
            if (statValue) {
                const totalVoix = candidate.total_voices != null ? candidate.total_voices : 0;
                if (totalVoix > 0) {
                    statValue.textContent = formatNumber(candidate.votes) + ' / ' + formatNumber(totalVoix) + ' voix';
                } else {
                    statValue.textContent = formatNumber(candidate.votes) + ' voix';
                }
            }
            
            const statPercentage = document.querySelector(`.stat-percentage[data-candidate-id="${candidate.id}"]`);
            if (statPercentage) {
                statPercentage.textContent = parseFloat(candidate.percentage).toFixed(1) + '%';
            }
            
            const barFill = document.querySelector(`.vote-bar-fill[data-candidate-id="${candidate.id}"]`);
            if (barFill) {
                barFill.style.width = candidate.percentage + '%';
                // Mettre à jour le texte dans la barre si visible
                if (candidate.percentage > 10) {
                    let barText = barFill.querySelector('span');
                    if (barText) {
                        barText.textContent = parseFloat(candidate.percentage).toFixed(1) + '%';
                    } else {
                        barText = document.createElement('span');
                        barText.className = 'text-xs font-bold text-white';
                        barText.textContent = parseFloat(candidate.percentage).toFixed(1) + '%';
                        barFill.appendChild(barText);
                    }
                } else {
                    // Supprimer le texte si le pourcentage est trop petit
                    const barText = barFill.querySelector('span');
                    if (barText) {
                        barText.remove();
                    }
                }
            }
        });
    }
    
    console.log('Dashboard mis à jour:', {
        progress: progress.toFixed(1) + '%',
        voted: `${data.voted_count}/${data.total_voters}`,
        nullVotes: data.null_votes
    });
}

// Rafraîchir les données toutes les 5 secondes
setInterval(async () => {
    try {
        const response = await fetch(`/tableau-de-bord/api/${electionId}`);
        const data = await response.json();
        updateDashboard(data);
    } catch (error) {
        console.error('Erreur lors de la mise à jour:', error);
    }
}, 5000);
</script>
@endpush
@endsection
