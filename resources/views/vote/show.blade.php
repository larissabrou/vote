@extends('layouts.app')

@section('title', 'Vote - ' . $election->title)

@section('content')
<div class="max-w-5xl mx-auto space-y-6 sm:space-y-8 px-2 sm:px-0 w-full overflow-x-hidden">
    <!-- Header Section -->
    <div class="card p-4 sm:p-6 lg:p-8 text-center">
        <div class="space-y-4 sm:space-y-6">
            @if($election->logo)
                <div class="inline-block">
                    <img src="{{ $election->logo_url }}" alt="{{ $election->title }}" 
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
                <h1 class="text-2xl sm:text-4xl md:text-5xl font-extrabold gradient-text mb-3 sm:mb-4 break-words">{{ $election->title }}</h1>
                <a href="{{ route('guide-vote') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700 font-medium mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    Lire le guide du vote (tout comprendre avant de voter)
                </a>
                <div class="inline-flex items-center gap-2 sm:gap-3 px-4 sm:px-6 py-2.5 sm:py-3 bg-gradient-to-r from-primary-50 to-[#ffe7c2] rounded-full border-2 border-primary-200 flex-wrap justify-center">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-primary-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="text-sm sm:text-lg text-gray-700 text-center">
                        Bonjour <strong class="text-gray-900">{{ $voter->full_name }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <form id="vote-form" class="space-y-8">
        @csrf
        @foreach($candidatesByPosition as $position => $positionData)
            @php
                $positionSlug = \Illuminate\Support\Str::slug($position);
            @endphp
            <div class="card p-4 sm:p-6 lg:p-8">
                <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="text-xl sm:text-3xl font-bold gradient-text mb-2 break-words">{{ $position }}</h2>
                        <p class="text-sm sm:text-base text-gray-600">Vous avez <strong class="text-primary-600">{{ $positionData['vote_count'] }}</strong> voix disponible(s) pour cette position</p>
                    </div>
                    <div class="flex items-center gap-3 sm:gap-4 flex-shrink-0">
                        <span class="text-sm text-gray-600">Bulletins nuls:</span>
                                <button type="button" class="min-w-[2.5rem] min-h-[2.5rem] w-10 h-10 rounded-full touch-manipulation bg-gradient-to-r from-red-500 to-red-600 text-white hover:from-red-600 hover:to-red-700 transition-all font-bold flex items-center justify-center shadow-lg hover:shadow-xl transform hover:scale-110 btn-null-minus" 
                                data-position="{{ $position }}">-</button>
                        <span class="text-2xl font-extrabold text-gray-900 min-w-[3rem] text-center null-vote-count bg-gray-100 rounded-xl py-2" 
                              data-position="{{ $position }}">0</span>
                        <button type="button" class="min-w-[2.5rem] min-h-[2.5rem] w-10 h-10 rounded-full touch-manipulation bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:from-orange-600 hover:to-orange-700 transition-all font-bold flex items-center justify-center shadow-lg hover:shadow-xl transform hover:scale-110 btn-null-plus" 
                                data-position="{{ $position }}">+</button>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    @foreach($positionData['candidates'] as $candidate)
                        <div class="candidate-vote-card bg-gradient-to-br from-gray-50 to-white rounded-xl p-4 sm:p-6 text-center border-2 border-gray-200 hover:border-primary-400 hover:shadow-xl transition-all duration-300 group" 
                             data-candidate-id="{{ $candidate->id }}"
                             data-position="{{ $position }}">
                            @if($candidate->photo)
                                <div class="relative inline-block mb-3 sm:mb-4">
                                    <img src="{{ asset($candidate->photo) }}" alt="{{ $candidate->full_name }}"
                                         class="w-20 h-20 sm:w-28 sm:h-28 rounded-full object-cover shadow-xl border-4 border-white group-hover:scale-110 transition-transform duration-300">
                                </div>
                            @else
                                <div class="w-20 h-20 sm:w-28 sm:h-28 mx-auto mb-3 sm:mb-4 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 flex items-center justify-center shadow-xl border-4 border-white group-hover:scale-110 transition-transform duration-300">
                                    <span class="text-2xl sm:text-3xl">👤</span>
                                </div>
                            @endif
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1 break-words">{{ $candidate->full_name }}</h3>
                            <div class="flex items-center justify-center gap-3 sm:gap-4">
                                <button type="button" class="min-w-[2.75rem] min-h-[2.75rem] w-11 h-11 sm:w-12 sm:h-12 rounded-full touch-manipulation bg-gradient-to-r from-red-500 to-red-600 text-white hover:from-red-600 hover:to-red-700 transition-all font-bold flex items-center justify-center shadow-lg hover:shadow-xl transform hover:scale-110 btn-minus" 
                                        data-candidate-id="{{ $candidate->id }}"
                                        data-position="{{ $position }}">-</button>
                                <span class="text-2xl sm:text-3xl font-extrabold text-gray-900 min-w-[3rem] sm:min-w-[4rem] text-center vote-count bg-gray-100 rounded-xl py-2" 
                                      data-candidate-id="{{ $candidate->id }}">0</span>
                                <button type="button" class="min-w-[2.75rem] min-h-[2.75rem] w-11 h-11 sm:w-12 sm:h-12 rounded-full touch-manipulation bg-gradient-to-r from-emerald-500 to-green-600 text-white hover:from-emerald-600 hover:to-green-700 transition-all font-bold flex items-center justify-center shadow-lg hover:shadow-xl transform hover:scale-110 btn-plus" 
                                        data-candidate-id="{{ $candidate->id }}"
                                        data-position="{{ $position }}">+</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="card p-4 sm:p-6 lg:p-8 bg-gradient-to-br from-primary-50 to-[#ffe7c2] border-primary-200">
            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4 sm:mb-6 flex items-center gap-3">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Résumé de votre vote
            </h3>
            <div class="mb-6 space-y-3">
                @foreach($candidatesByPosition as $position => $positionData)
                    @php
                        $positionSlug = \Illuminate\Support\Str::slug($position);
                    @endphp
                    <div class="p-4 bg-white rounded-xl border-2 border-primary-200">
                        <p class="text-lg text-center">
                            <strong>{{ $position }}:</strong> 
                            <span id="used-votes-{{ $positionSlug }}" class="font-extrabold text-primary-600 text-xl">0</span> / 
                            <span class="font-extrabold text-gray-900 text-xl">{{ $positionData['vote_count'] }}</span> voix
                        </p>
                    </div>
                @endforeach
            </div>
            <div id="vote-details" class="space-y-3"></div>
        </div>

        <!-- Zone de signature -->
        <div class="card p-4 sm:p-6">
            <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-4">Signature</h3>
            <p class="text-sm text-gray-600 mb-4">Veuillez signer ci-dessous pour valider votre vote :</p>
            
            <div class="border-2 border-gray-300 rounded-lg bg-white relative" id="signature-container">
                <canvas id="signature-canvas" class="w-full cursor-crosshair touch-none" style="min-height: 200px;"></canvas>
                <div id="signature-placeholder" class="absolute inset-0 flex items-center justify-center text-gray-400 pointer-events-none">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                        <p class="text-sm">Signez ici</p>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-3 mt-4">
                <button type="button" id="clear-signature" class="px-4 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors touch-manipulation">
                    Effacer
                </button>
                <div class="flex-1 min-w-0"></div>
                <p class="text-sm text-gray-600 self-center" id="signature-status">
                    <span class="text-red-500">✗</span> Signature requise
                </p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button type="submit" class="btn btn-primary group" id="submit-vote" disabled>
                <svg class="w-5 h-5 mr-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Valider mon vote
            </button>
        </div>
    </form>
</div>

<!-- Modal Popup -->
<div id="modal-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center transition-opacity duration-300">
    <div id="modal-content" class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform scale-95 transition-all duration-300">
        <div class="p-6">
            <div class="flex items-center justify-center mb-4">
                <div class="w-16 h-16 bg-gradient-to-br from-accent-orange to-orange-500 rounded-full flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
            <h3 id="modal-title" class="text-2xl font-bold text-gray-900 text-center mb-3"></h3>
            <p id="modal-message" class="text-gray-600 text-center mb-6"></p>
            <button id="modal-close" class="w-full bg-gradient-to-r from-primary-500 to-primary-600 text-white font-semibold py-3 px-6 rounded-xl hover:from-primary-600 hover:to-primary-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                Compris
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Stocker les limites de voix par position
const voteLimitsByPosition = {
    @foreach($candidatesByPosition as $position => $positionData)
        '{{ $position }}': {{ $positionData['vote_count'] }},
    @endforeach
};

// Données des positions (créées une seule fois)
@php
    $positionDataGlobal = [];
    foreach($candidatesByPosition as $position => $data) {
        $positionDataGlobal[] = [
            'position' => $position,
            'vote_count' => $data['vote_count'],
            'slug' => \Illuminate\Support\Str::slug($position)
        ];
    }
@endphp
const positionData = @json($positionDataGlobal);

let votes = {};
let nullVotesByPosition = {};
let signatureData = null; // Stockera la signature en base64

// Gestion de la signature
const canvas = document.getElementById('signature-canvas');
const ctx = canvas.getContext('2d', { willReadFrequently: true });
const placeholder = document.getElementById('signature-placeholder');
const clearBtn = document.getElementById('clear-signature');
const signatureStatus = document.getElementById('signature-status');
let isDrawing = false;
let lastX = 0;
let lastY = 0;
let hasSignature = false; // Flag pour suivre si une signature existe

// Ajuster la taille du canvas
function resizeCanvas() {
    const container = document.getElementById('signature-container');
    const rect = container.getBoundingClientRect();
    canvas.width = rect.width;
    canvas.height = 200;
    
    // Configuration du style de dessin
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    
    // Réinitialiser le flag de signature après redimensionnement
    hasSignature = false;
    updateSignatureStatus();
}

// Initialiser le canvas
resizeCanvas();
window.addEventListener('resize', resizeCanvas);

// Fonctions de dessin
function startDrawing(e) {
    isDrawing = true;
    const rect = canvas.getBoundingClientRect();
    lastX = e.clientX - rect.left || (e.touches && e.touches[0].clientX - rect.left);
    lastY = e.clientY - rect.top || (e.touches && e.touches[0].clientY - rect.top);
    placeholder.style.display = 'none';
}

function draw(e) {
    if (!isDrawing) return;
    e.preventDefault();
    
    const rect = canvas.getBoundingClientRect();
    const currentX = e.clientX - rect.left || (e.touches && e.touches[0].clientX - rect.left);
    const currentY = e.clientY - rect.top || (e.touches && e.touches[0].clientY - rect.top);
    
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(currentX, currentY);
    ctx.stroke();
    
    lastX = currentX;
    lastY = currentY;
    
    // Marquer qu'une signature existe dès qu'on dessine (une fois qu'on a tracé un trait)
    // On ne vérifie pas à chaque mouvement pour des raisons de performance
    if (!hasSignature) {
        hasSignature = true;
        signatureData = canvas.toDataURL('image/png');
        console.log('Signature détectée! hasSignature =', hasSignature);
        updateSignatureStatus();
        updateSummary();
    }
}

function stopDrawing() {
    if (isDrawing) {
        isDrawing = false;
        // Si on a déjà marqué qu'une signature existe, on la garde
        // Sinon, on vérifie si une signature existe maintenant
        if (!hasSignature) {
            hasSignature = checkSignatureExists();
        }
        // Toujours mettre à jour signatureData si hasSignature est true
        if (hasSignature) {
            signatureData = canvas.toDataURL('image/png');
            console.log('Signature mise à jour dans stopDrawing, hasSignature =', hasSignature);
        }
        updateSignatureStatus();
        updateSummary();
    }
}

// Événements pour souris
canvas.addEventListener('mousedown', startDrawing);
canvas.addEventListener('mousemove', draw);
canvas.addEventListener('mouseup', stopDrawing);
canvas.addEventListener('mouseout', stopDrawing);

// Événements pour tactile
canvas.addEventListener('touchstart', startDrawing);
canvas.addEventListener('touchmove', draw);
canvas.addEventListener('touchend', stopDrawing);

// Effacer la signature
clearBtn.addEventListener('click', function() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    placeholder.style.display = 'flex';
    signatureData = null;
    hasSignature = false;
    updateSignatureStatus();
    updateSummary();
});

// Vérifier si une signature existe sur le canvas
function checkSignatureExists() {
    if (!canvas || !ctx) return false;
    
    try {
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        
        // Vérifier si au moins un pixel non-transparent existe
        // On vérifie les canaux RGB (index 0, 1, 2) et ignorons alpha (index 3)
        // On cherche des pixels avec une valeur significative (pas juste du bruit)
        for (let i = 0; i < data.length; i += 4) {
            const r = data[i];
            const g = data[i + 1];
            const b = data[i + 2];
            // Si au moins un canal RGB a une valeur > 10 (pour ignorer le bruit), il y a du contenu
            if (r > 10 || g > 10 || b > 10) {
                return true;
            }
        }
        return false;
    } catch (e) {
        console.error('Erreur lors de la vérification de la signature:', e);
        return false;
    }
}

// Mettre à jour la signature en base64 (fonction utilitaire, maintenant appelée depuis stopDrawing)
function updateSignature() {
    hasSignature = checkSignatureExists();
    if (hasSignature) {
        signatureData = canvas.toDataURL('image/png');
    }
    updateSignatureStatus();
    updateSummary();
}

// Mettre à jour le statut de la signature
function updateSignatureStatus() {
    if (hasSignature) {
        signatureStatus.innerHTML = '<span class="text-green-500">✓</span> Signature complétée';
        signatureStatus.className = 'text-sm text-green-600 self-center';
    } else {
        signatureStatus.innerHTML = '<span class="text-red-500">✗</span> Signature requise';
        signatureStatus.className = 'text-sm text-red-600 self-center';
    }
}

// Fonctions pour gérer le modal popup
function showModal(title, message) {
    const overlay = document.getElementById('modal-overlay');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');
    const content = document.getElementById('modal-content');
    
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    overlay.classList.remove('hidden');
    
    // Animation d'entrée
    setTimeout(() => {
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
    }, 10);
}

function hideModal() {
    const overlay = document.getElementById('modal-overlay');
    const content = document.getElementById('modal-content');
    
    content.classList.remove('scale-100');
    content.classList.add('scale-95');
    
    setTimeout(() => {
        overlay.classList.add('hidden');
    }, 300);
}

// Fermer le modal en cliquant sur le bouton ou l'overlay
document.getElementById('modal-close').addEventListener('click', hideModal);
document.getElementById('modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) {
        hideModal();
    }
});

// Fonctions utilitaires (définies avant leur utilisation)
function updateNullVoteDisplay(position) {
    const countElement = document.querySelector(`.null-vote-count[data-position="${position}"]`);
    if (countElement) {
        countElement.textContent = nullVotesByPosition[position] || 0;
    }
}

function getUsedVotesForPosition(position) {
    let total = 0;
    // Votes pour les candidats
    document.querySelectorAll(`.candidate-vote-card[data-position="${position}"]`).forEach(card => {
        const candidateId = card.dataset.candidateId;
        total += votes[candidateId] || 0;
    });
    // Bulletins nuls pour cette position
    total += nullVotesByPosition[position] || 0;
    return total;
}

function getTotalUsedVotes() {
    const candidateVotes = Object.values(votes).reduce((sum, count) => sum + count, 0);
    const nullVotesTotal = Object.values(nullVotesByPosition).reduce((sum, count) => sum + count, 0);
    return candidateVotes + nullVotesTotal;
}

function updateVoteDisplay(candidateId) {
    const countElement = document.querySelector(`.vote-count[data-candidate-id="${candidateId}"]`);
    if (countElement) {
        countElement.textContent = votes[candidateId] || 0;
    }
}

// Gérer les votes pour chaque candidat
document.querySelectorAll('.btn-plus').forEach(btn => {
    btn.addEventListener('click', function() {
        const candidateId = this.dataset.candidateId;
        const position = this.dataset.position;
        const currentCount = votes[candidateId] || 0;
        const maxVotes = voteLimitsByPosition[position] || 0;
        const usedVotesForPosition = getUsedVotesForPosition(position);
        
        if (usedVotesForPosition < maxVotes) {
            votes[candidateId] = currentCount + 1;
            updateVoteDisplay(candidateId);
            updateSummary();
        } else {
            showModal(
                'Limite de voix atteinte',
                `Vous avez atteint votre nombre maximum de voix (${maxVotes}) pour la position "${position}".`
            );
        }
    });
});

document.querySelectorAll('.btn-minus').forEach(btn => {
    btn.addEventListener('click', function() {
        const candidateId = this.dataset.candidateId;
        const currentCount = votes[candidateId] || 0;
        
        if (currentCount > 0) {
            votes[candidateId] = currentCount - 1;
            if (votes[candidateId] === 0) {
                delete votes[candidateId];
            }
            updateVoteDisplay(candidateId);
            updateSummary();
        }
    });
});

// Gérer les bulletins nuls par position
document.querySelectorAll('.btn-null-plus').forEach(btn => {
    btn.addEventListener('click', function() {
        const position = this.dataset.position;
        const maxVotes = voteLimitsByPosition[position] || 0;
        const usedVotesForPosition = getUsedVotesForPosition(position);
        
        if (usedVotesForPosition < maxVotes) {
            nullVotesByPosition[position] = (nullVotesByPosition[position] || 0) + 1;
            updateNullVoteDisplay(position);
            updateSummary();
        } else {
            showModal(
                'Limite de voix atteinte',
                `Vous avez atteint votre nombre maximum de voix (${maxVotes}) pour la position "${position}".`
            );
        }
    });
});

document.querySelectorAll('.btn-null-minus').forEach(btn => {
    btn.addEventListener('click', function() {
        const position = this.dataset.position;
        const currentNullVotes = nullVotesByPosition[position] || 0;
        
        if (currentNullVotes > 0) {
            nullVotesByPosition[position] = currentNullVotes - 1;
            if (nullVotesByPosition[position] === 0) {
                delete nullVotesByPosition[position];
            }
            updateNullVoteDisplay(position);
            updateSummary();
        }
    });
});

function updateNullVoteDisplay(position) {
    const countElement = document.querySelector(`.null-vote-count[data-position="${position}"]`);
    if (countElement) {
        countElement.textContent = nullVotesByPosition[position] || 0;
    }
}

function getUsedVotesForPosition(position) {
    let total = 0;
    // Votes pour les candidats
    document.querySelectorAll(`.candidate-vote-card[data-position="${position}"]`).forEach(card => {
        const candidateId = card.dataset.candidateId;
        total += votes[candidateId] || 0;
    });
    // Bulletins nuls pour cette position
    total += nullVotesByPosition[position] || 0;
    return total;
}

function getTotalUsedVotes() {
    const candidateVotes = Object.values(votes).reduce((sum, count) => sum + count, 0);
    const nullVotesTotal = Object.values(nullVotesByPosition).reduce((sum, count) => sum + count, 0);
    return candidateVotes + nullVotesTotal;
}

function updateSummary() {
    const detailsDiv = document.getElementById('vote-details');
    if (!detailsDiv) return;
    detailsDiv.innerHTML = '';
    
    // Mettre à jour les compteurs par position (utilise la variable globale)
    positionData.forEach(pos => {
        const usedVotes = getUsedVotesForPosition(pos.position);
        const element = document.getElementById('used-votes-' + pos.slug);
        if (element) {
            element.textContent = usedVotes;
        }
    });
    
    // Afficher le détail des votes
    Object.keys(votes).forEach(candidateId => {
        if (votes[candidateId] > 0) {
            const candidateCard = document.querySelector(`.candidate-vote-card[data-candidate-id="${candidateId}"]`);
            const candidateName = candidateCard.querySelector('h3').textContent;
            const div = document.createElement('div');
            div.textContent = `${candidateName}: ${votes[candidateId]} voix`;
            detailsDiv.appendChild(div);
        }
    });
    
    // Afficher les bulletins nuls par position
    Object.keys(nullVotesByPosition).forEach(position => {
        if (nullVotesByPosition[position] > 0) {
            const div = document.createElement('div');
            div.textContent = `Bulletins nuls (${position}): ${nullVotesByPosition[position]}`;
            detailsDiv.appendChild(div);
        }
    });
    
    // Vérifier que toutes les positions respectent leurs limites (utilise la variable globale)
    let allValid = true;
    positionData.forEach(pos => {
        const used = getUsedVotesForPosition(pos.position);
        if (used > pos.vote_count) {
            allValid = false;
        }
    });
    
    const totalUsed = getTotalUsedVotes();
    // Utiliser le flag hasSignature au lieu de recalculer
    const submitButton = document.getElementById('submit-vote');
    if (submitButton) {
        const shouldDisable = totalUsed === 0 || !allValid || !hasSignature;
        submitButton.disabled = shouldDisable;
        
        // Debug: afficher les raisons de désactivation
        if (shouldDisable) {
            console.log('Bouton désactivé:', {
                totalUsed: totalUsed,
                allValid: allValid,
                hasSignature: hasSignature
            });
        } else {
            console.log('Bouton activé!');
        }
    }
}

// Initialiser les compteurs de bulletins nuls au chargement
@foreach($candidatesByPosition as $position => $positionData)
    updateNullVoteDisplay('{{ $position }}');
@endforeach

// Initialiser l'état du bouton au chargement
updateSummary();

document.getElementById('vote-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Vérifier que toutes les positions respectent leurs limites (utilise la variable globale)
    let allValid = true;
    let errorMessage = '';
    positionData.forEach(pos => {
        const used = getUsedVotesForPosition(pos.position);
        if (used > pos.vote_count) {
            allValid = false;
            errorMessage = `Vous avez dépassé votre nombre de voix pour la position "${pos.position}". Maximum: ${pos.vote_count}, Vous avez voté: ${used}.`;
        }
    });
    
    if (!allValid) {
        showModal('Erreur de validation', errorMessage);
        return;
    }
    
    const totalUsed = getTotalUsedVotes();
    if (totalUsed === 0) {
        showModal('Vote requis', 'Veuillez voter pour au moins un candidat.');
        return;
    }
    
    // Vérifier la signature (utiliser le flag au lieu de recalculer)
    if (!hasSignature) {
        showModal('Signature requise', 'Veuillez signer pour valider votre vote.');
        return;
    }
    
    // Construire le tableau de votes
    const votesArray = [];
    
    Object.keys(votes).forEach(candidateId => {
        for (let i = 0; i < votes[candidateId]; i++) {
            votesArray.push({
                candidate_id: candidateId,
                is_null: false
            });
        }
    });
    
    // Ajouter les bulletins nuls par position
    Object.keys(nullVotesByPosition).forEach(position => {
        for (let i = 0; i < nullVotesByPosition[position]; i++) {
            votesArray.push({
                candidate_id: null,
                is_null: true,
                position: position
            });
        }
    });

    // Bulletins blancs = voix non utilisées par position (candidate_id null, is_null false)
    positionData.forEach(pos => {
        const used = getUsedVotesForPosition(pos.position);
        const blankCount = pos.vote_count - used;
        for (let i = 0; i < blankCount; i++) {
            votesArray.push({
                candidate_id: null,
                is_null: false,
                position: pos.position
            });
        }
    });
    
    // Ajouter la signature
    const signature = canvas.toDataURL('image/png');
    
    console.log('Envoi des votes:', votesArray);
    
    try {
        const response = await fetch('{{ route("voter.soumettre", $election->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                votes: votesArray,
                signature: signature
            })
        });
        
        const data = await response.json();
        console.log('Réponse du serveur:', data);
        
        if (response.ok) {
            showModal('Succès', 'Vote enregistré avec succès! Vous allez être redirigé vers les résultats.');
            const redirectUrl = data.redirect_url || '{{ route("accueil") }}';
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 1500);
        } else {
            console.error('Erreur serveur:', data);
            showModal('Erreur', data.error || data.message || 'Une erreur est survenue.');
        }
    } catch (error) {
        console.error('Erreur lors de l\'envoi:', error);
        showModal('Erreur', 'Une erreur est survenue lors de l\'envoi du vote: ' + error.message);
    }
});
</script>
@endpush
@endsection

