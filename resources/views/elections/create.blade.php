@extends('layouts.app')

@section('title', 'Créer une Élection')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 sm:space-y-8 px-2 sm:px-0">
    <div class="text-center mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-4xl md:text-5xl font-extrabold gradient-text mb-3">Créer une nouvelle élection</h1>
        <p class="text-gray-600 text-base sm:text-lg">Remplissez les informations ci-dessous pour créer votre élection</p>
    </div>

    <form action="{{ route('elections.enregistrer') }}" method="POST" enctype="multipart/form-data" id="election-form" class="space-y-6 sm:space-y-8">
        @csrf

        <div class="card p-4 sm:p-6 lg:p-8 space-y-6">
            <h2 class="text-xl sm:text-2xl font-bold gradient-text flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Informations générales
            </h2>
            
            <div class="form-group">
                <label for="title">Titre de l'élection *</label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
            </div>

            <div class="form-group">
                <label for="logo" class="block mb-1">Logo de l'élection (max 2 Mo)</label>
                <div class="file-input-fr flex items-center gap-2 flex-wrap mt-1">
                    <input type="file" name="logo" id="logo" class="file-input-hidden" accept="image/*" aria-label="Choisir un fichier">
                    <label for="logo" class="btn btn-parcourir cursor-pointer mb-0">Parcourir</label>
                    <span class="file-name text-gray-500 text-sm">Aucun fichier sélectionné</span>
                </div>
                @error('logo')
                    <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                @enderror
                <small class="text-gray-500 text-sm mt-1 block" id="logo-size-info"></small>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="election_date">Date de début *</label>
                    <input type="text" name="election_date" id="election_date" class="form-control" value="{{ old('election_date') && preg_match('/^\d{4}-\d{2}-\d{2}$/', old('election_date')) ? \Carbon\Carbon::parse(old('election_date'))->format('d/m/Y') : old('election_date') }}" placeholder="jj/mm/aaaa" maxlength="10" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="end_date">Date de fin (optionnel – plusieurs jours)</label>
                    <input type="text" name="end_date" id="end_date" class="form-control" value="{{ old('end_date') && preg_match('/^\d{4}-\d{2}-\d{2}$/', old('end_date')) ? \Carbon\Carbon::parse(old('end_date'))->format('d/m/Y') : old('end_date') }}" placeholder="jj/mm/aaaa" maxlength="10" autocomplete="off">
                    <small class="text-gray-500 text-sm mt-1 block">Laissez vide pour une élection sur un seul jour.</small>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="start_time">Heure de début *</label>
                    <div class="flex gap-2 items-center">
                        @php
                            $oldStartTime = old('start_time', '');
                            $startHour = $oldStartTime ? explode(':', $oldStartTime)[0] : '';
                            $startMinute = $oldStartTime && isset(explode(':', $oldStartTime)[1]) ? explode(':', $oldStartTime)[1] : '';
                        @endphp
                        <select name="start_time_hour" id="start_time_hour" class="form-control" style="width: auto; min-width: 80px;" required>
                            <option value="">Heure</option>
                            @for($i = 0; $i < 24; $i++)
                                <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ $startHour == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                    {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                </option>
                            @endfor
                        </select>
                        <span class="text-gray-600 font-bold">:</span>
                        <select name="start_time_minute" id="start_time_minute" class="form-control" style="width: auto; min-width: 80px;" required>
                            <option value="">Minute</option>
                            @for($i = 0; $i < 60; $i += 1)
                                <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ $startMinute == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                    {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                </option>
                            @endfor
                        </select>
                        <input type="hidden" name="start_time" id="start_time" value="{{ old('start_time', '') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label for="end_time">Heure de fin *</label>
                    <div class="flex gap-2 items-center">
                        @php
                            $oldEndTime = old('end_time', '');
                            $endHour = $oldEndTime ? explode(':', $oldEndTime)[0] : '';
                            $endMinute = $oldEndTime && isset(explode(':', $oldEndTime)[1]) ? explode(':', $oldEndTime)[1] : '';
                        @endphp
                        <select name="end_time_hour" id="end_time_hour" class="form-control" style="width: auto; min-width: 80px;" required>
                            <option value="">Heure</option>
                            @for($i = 0; $i < 24; $i++)
                                <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ $endHour == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                    {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                </option>
                            @endfor
                        </select>
                        <span class="text-gray-600 font-bold">:</span>
                        <select name="end_time_minute" id="end_time_minute" class="form-control" style="width: auto; min-width: 80px;" required>
                            <option value="">Minute</option>
                            @for($i = 0; $i < 60; $i += 1)
                                <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ $endMinute == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                    {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                </option>
                            @endfor
                        </select>
                        <input type="hidden" name="end_time" id="end_time" value="{{ old('end_time', '') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-8 space-y-6">
            <h2 class="text-2xl font-bold gradient-text flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Candidats
            </h2>
            <div id="candidates-container" class="space-y-4">
                @php
                    $candidatesData = session('candidates_data', []);
                    if (empty($candidatesData)) {
                        $candidatesData = [['first_name' => old('candidates.0.first_name', ''), 'last_name' => old('candidates.0.last_name', ''), 'position' => old('candidates.0.position', '')]];
                    }
                @endphp
                @foreach($candidatesData as $index => $candidate)
                <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl p-6 candidate-item border-2 border-gray-200" data-index="{{ $index }}">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-primary-500 text-white rounded-lg flex items-center justify-center font-bold">{{ $index + 1 }}</span>
                        Candidat {{ $index + 1 }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="form-group">
                            <label>Prénom *</label>
                            <input type="text" name="candidates[{{ $index }}][first_name]" class="form-control" value="{{ old("candidates.$index.first_name", $candidate['first_name'] ?? '') }}" required>
                            @error("candidates.$index.first_name")
                                <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Nom *</label>
                            <input type="text" name="candidates[{{ $index }}][last_name]" class="form-control" value="{{ old("candidates.$index.last_name", $candidate['last_name'] ?? '') }}" required>
                            @error("candidates.$index.last_name")
                                <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group mb-4">
                        <label>Poste voulu *</label>
                        <input type="text" name="candidates[{{ $index }}][position]" class="form-control" value="{{ old("candidates.$index.position", $candidate['position'] ?? '') }}" required>
                        @error("candidates.$index.position")
                            <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group mb-4">
                        <label>Photo (max 2 Mo)</label>
                        <div class="file-input-fr flex items-center gap-2 flex-wrap mt-1">
                            <input type="file" name="candidates[{{ $index }}][photo]" id="candidate-photo-{{ $index }}" class="file-input-hidden candidate-photo" accept="image/*" data-index="{{ $index }}" aria-label="Choisir un fichier">
                            <label for="candidate-photo-{{ $index }}" class="btn btn-parcourir cursor-pointer mb-0">Parcourir</label>
                            <span class="file-name text-gray-500 text-sm">Aucun fichier sélectionné</span>
                        </div>
                        @error("candidates.$index.photo")
                            <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                        @enderror
                        <small class="text-gray-500 text-sm mt-1 block file-size-info" id="size-info-{{ $index }}"></small>
                    </div>
                    @if($index > 0)
                        <button type="button" class="btn btn-danger btn-sm remove-candidate">Supprimer</button>
                    @endif
                </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-primary text-white hover:text-white" id="add-candidate"><span class="text-white mr-2 text-lg font-bold">+</span> Ajouter un candidat</button>
        </div>

        <div class="card p-8">
            <h2 class="text-2xl font-bold gradient-text mb-4 flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Inscription des votants
            </h2>
            @php
                $votersSource = old('voters_source', 'link');
            @endphp
            <div class="form-group">
                <label class="block mb-2">Choix du mode *</label>
                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="voters_source" value="link" {{ $votersSource === 'link' ? 'checked' : '' }}>
                        <span>Lien public (les votants saisissent eux-mêmes leur email)</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="voters_source" value="excel" {{ $votersSource === 'excel' ? 'checked' : '' }}>
                        <span>Importer un fichier Excel maintenant</span>
                    </label>
                </div>
            </div>

            <div id="voters-file-block" class="form-group">
                <label for="voters_file" class="block mb-1">Fichier Excel des votants</label>
                <div class="file-input-fr flex items-center gap-2 flex-wrap mt-1">
                    <input type="file" name="voters_file" id="voters_file" class="file-input-hidden" accept=".xlsx,.xls" aria-label="Choisir un fichier">
                    <label for="voters_file" class="btn btn-parcourir cursor-pointer mb-0">Parcourir</label>
                    <span class="file-name text-gray-500 text-sm">Aucun fichier sélectionné</span>
                </div>
                <div class="flex items-center gap-2 mt-1 flex-wrap">
                    <small class="text-gray-500 text-sm">Format requis: Colonnes email, nom, prenom, nombre_voix</small>
                    <a href="/elections/modele-votants" class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-primary-600 hover:bg-primary-50 border-2 border-primary-500 hover:border-primary-600 flex-shrink-0" title="Télécharger le modèle Excel (.xlsx)" aria-label="Télécharger le modèle Excel">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </a>
                </div>
                @error('voters_file')
                    <p class="text-red-600 text-sm mt-2 font-medium" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <p class="text-gray-600">
                Si vous choisissez le lien public, vous pourrez le partager juste après la création.
            </p>
        </div>

        <div class="flex gap-4 justify-end">
            <a href="{{ route('elections.liste') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">Créer l'élection</button>
        </div>
    </form>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.file-size-warning {
    @apply text-yellow-600;
}
.file-size-error {
    @apply text-red-600;
}
/* Cache l'input fichier : plus de "Browse..." visible, seul le label "Parcourir" s'affiche */
.file-input-hidden {
    position: absolute !important;
    width: 0.01px !important;
    height: 0.01px !important;
    opacity: 0 !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    pointer-events: none;
}
.file-input-fr {
    position: relative;
}
.file-input-fr label[for]:not([class*="block"]) {
    pointer-events: auto;
}
/* Bouton Parcourir : fond blanc, texte et bordure couleur primaire */
.btn-parcourir {
    @apply bg-white text-primary-600 border-2 border-primary-500 font-semibold;
}
.btn-parcourir:hover {
    @apply bg-primary-50 text-primary-700 border-primary-600;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#election_date', {
            dateFormat: 'd/m/Y',
            allowInput: true,
            locale: 'fr',
            disableMobile: false
        });
        flatpickr('#end_date', {
            dateFormat: 'd/m/Y',
            allowInput: true,
            locale: 'fr',
            disableMobile: false
        });
    }
});
</script>
<script>
// Récupérer le nombre de candidats existants
let candidateIndex = {{ count(session('candidates_data', [['first_name' => '', 'last_name' => '', 'position' => '']])) }};

// Fonction pour formater la taille de fichier
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Vérifier la taille des fichiers
function checkFileSize(input, maxSizeKB) {
    const file = input.files[0];
    if (file) {
        const fileSizeKB = file.size / 1024;
        const infoElement = input.closest('.form-group').querySelector('.file-size-info');
        
        if (infoElement) {
            if (fileSizeKB > maxSizeKB) {
                infoElement.textContent = `⚠️ Fichier trop volumineux: ${formatFileSize(file.size)} (max ${maxSizeKB} KB)`;
                infoElement.className = 'file-size-info file-size-error';
                input.setCustomValidity(`Le fichier ne doit pas dépasser ${maxSizeKB} KB`);
            } else {
                infoElement.textContent = `✓ Taille: ${formatFileSize(file.size)}`;
                infoElement.className = 'file-size-info';
                input.setCustomValidity('');
            }
        }
    }
}

// Mettre à jour le libellé "Aucun fichier sélectionné" / nom du fichier
function updateFileNameLabel(input) {
    const wrapper = input.closest('.file-input-fr');
    if (!wrapper) return;
    const nameEl = wrapper.querySelector('.file-name');
    if (!nameEl) return;
    if (input.files && input.files.length > 0) {
        nameEl.textContent = input.files[0].name;
    } else {
        nameEl.textContent = 'Aucun fichier sélectionné';
    }
}

// Vérifier la taille du logo
document.getElementById('logo')?.addEventListener('change', function() {
    checkFileSize(this, 2048);
    updateFileNameLabel(this);
});

// Vérifier la taille des photos de candidats
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('candidate-photo')) {
        checkFileSize(e.target, 2048);
        updateFileNameLabel(e.target);
    }
});

// Libellé fichier pour le fichier votants
document.getElementById('voters_file')?.addEventListener('change', function() {
    updateFileNameLabel(this);
});

function updateVotersSourceMode() {
    const selected = document.querySelector('input[name="voters_source"]:checked')?.value || 'link';
    const fileBlock = document.getElementById('voters-file-block');
    const fileInput = document.getElementById('voters_file');
    if (!fileBlock || !fileInput) return;

    if (selected === 'excel') {
        fileBlock.classList.remove('hidden');
        fileInput.required = true;
    } else {
        fileBlock.classList.add('hidden');
        fileInput.required = false;
        fileInput.value = '';
        updateFileNameLabel(fileInput);
    }
}

document.querySelectorAll('input[name="voters_source"]').forEach(function(radio) {
    radio.addEventListener('change', updateVotersSourceMode);
});
updateVotersSourceMode();

// Ajouter un candidat
document.getElementById('add-candidate').addEventListener('click', function() {
    const container = document.getElementById('candidates-container');
    const existingCandidates = container.querySelectorAll('.candidate-item');
    const newIndex = existingCandidates.length;
    
    const newCandidate = document.createElement('div');
    newCandidate.className = 'bg-gradient-to-br from-gray-50 to-white rounded-xl p-6 candidate-item border-2 border-gray-200';
    newCandidate.setAttribute('data-index', newIndex);
    newCandidate.innerHTML = `
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-primary-500 text-white rounded-lg flex items-center justify-center font-bold">${newIndex + 1}</span>
            Candidat ${newIndex + 1}
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="form-group">
                <label>Prénom *</label>
                <input type="text" name="candidates[${newIndex}][first_name]" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Nom *</label>
                <input type="text" name="candidates[${newIndex}][last_name]" class="form-control" required>
            </div>
        </div>
        <div class="form-group mb-4">
            <label>Poste voulu *</label>
            <input type="text" name="candidates[${newIndex}][position]" class="form-control" required>
        </div>
        <div class="form-group mb-4">
            <label>Photo (max 2 Mo)</label>
            <div class="file-input-fr flex items-center gap-2 flex-wrap mt-1">
                <input type="file" name="candidates[${newIndex}][photo]" id="candidate-photo-${newIndex}" class="file-input-hidden candidate-photo" accept="image/*" data-index="${newIndex}" aria-label="Choisir un fichier">
                <label for="candidate-photo-${newIndex}" class="btn btn-parcourir cursor-pointer mb-0">Parcourir</label>
                <span class="file-name text-gray-500 text-sm">Aucun fichier sélectionné</span>
            </div>
            <small class="text-gray-500 text-sm mt-1 block file-size-info" id="size-info-${newIndex}"></small>
        </div>
        <button type="button" class="btn btn-danger btn-sm remove-candidate">Supprimer</button>
    `;
    container.appendChild(newCandidate);
    candidateIndex++;
    
    // Ajouter l'événement de suppression
    newCandidate.querySelector('.remove-candidate').addEventListener('click', function() {
        newCandidate.remove();
        // Renuméroter les candidats
        updateCandidateNumbers();
    });
    
    // Ajouter l'événement pour vérifier la taille du fichier
    const photoInput = newCandidate.querySelector('.candidate-photo');
    if (photoInput) {
        photoInput.addEventListener('change', function() {
            checkFileSize(this, 2048);
        });
    }
});

// Renuméroter les candidats après suppression
function updateCandidateNumbers() {
    const candidates = document.querySelectorAll('.candidate-item');
    candidates.forEach((candidate, index) => {
        candidate.setAttribute('data-index', index);
        
        // Mettre à jour le titre et le badge
        const h3 = candidate.querySelector('h3');
        if (h3) {
            const badge = h3.querySelector('span');
            if (badge) {
                badge.textContent = index + 1;
            }
            // Mettre à jour le texte après le badge
            const textParts = h3.childNodes;
            for (let i = 0; i < textParts.length; i++) {
                if (textParts[i].nodeType === Node.TEXT_NODE && textParts[i].textContent.trim().startsWith('Candidat')) {
                    textParts[i].textContent = ` Candidat ${index + 1}`;
                    break;
                }
            }
        }
        
        // Mettre à jour les noms des champs
        const inputs = candidate.querySelectorAll('input[type="text"]');
        inputs.forEach(input => {
            const name = input.name;
            if (name.includes('candidates[')) {
                const newName = name.replace(/candidates\[\d+\]/, `candidates[${index}]`);
                input.name = newName;
            }
        });
        
        const fileInput = candidate.querySelector('input[type="file"]');
        if (fileInput) {
            const newName = fileInput.name.replace(/candidates\[\d+\]/, `candidates[${index}]`);
            fileInput.name = newName;
            fileInput.setAttribute('data-index', index);
            
            // Mettre à jour l'ID du small pour la taille du fichier
            const sizeInfo = candidate.querySelector('.file-size-info');
            if (sizeInfo) {
                sizeInfo.id = `size-info-${index}`;
            }
        }
    });
}

// Gérer la suppression des candidats
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-candidate')) {
        const candidateItem = e.target.closest('.candidate-item');
        if (candidateItem && document.querySelectorAll('.candidate-item').length > 1) {
            candidateItem.remove();
            updateCandidateNumbers();
        } else {
            window.showAppPopup('warning', 'Vous devez avoir au moins un candidat.', 'Attention');
        }
    }
});

// Combiner les sélecteurs d'heure et minute en format HH:MM
function updateTimeField(hourSelect, minuteSelect, hiddenInput) {
    const hour = hourSelect.value;
    const minute = minuteSelect.value;
    
    if (hour && minute) {
        hiddenInput.value = `${hour.padStart(2, '0')}:${minute.padStart(2, '0')}`;
    } else {
        hiddenInput.value = '';
    }
}

// Gérer les changements pour l'heure de début
document.addEventListener('DOMContentLoaded', function() {
    const startHourSelect = document.getElementById('start_time_hour');
    const startMinuteSelect = document.getElementById('start_time_minute');
    const startTimeHidden = document.getElementById('start_time');
    
    if (startHourSelect && startMinuteSelect && startTimeHidden) {
        // Initialiser la valeur si elle existe déjà
        if (startTimeHidden.value) {
            const [hour, minute] = startTimeHidden.value.split(':');
            if (hour && minute) {
                startHourSelect.value = hour.padStart(2, '0');
                startMinuteSelect.value = minute.padStart(2, '0');
            }
        }
        
        startHourSelect.addEventListener('change', function() {
            updateTimeField(startHourSelect, startMinuteSelect, startTimeHidden);
        });
        startMinuteSelect.addEventListener('change', function() {
            updateTimeField(startHourSelect, startMinuteSelect, startTimeHidden);
        });
    }
    
    // Gérer les changements pour l'heure de fin
    const endHourSelect = document.getElementById('end_time_hour');
    const endMinuteSelect = document.getElementById('end_time_minute');
    const endTimeHidden = document.getElementById('end_time');
    
    if (endHourSelect && endMinuteSelect && endTimeHidden) {
        // Initialiser la valeur si elle existe déjà
        if (endTimeHidden.value) {
            const [hour, minute] = endTimeHidden.value.split(':');
            if (hour && minute) {
                endHourSelect.value = hour.padStart(2, '0');
                endMinuteSelect.value = minute.padStart(2, '0');
            }
        }
        
        endHourSelect.addEventListener('change', function() {
            updateTimeField(endHourSelect, endMinuteSelect, endTimeHidden);
        });
        endMinuteSelect.addEventListener('change', function() {
            updateTimeField(endHourSelect, endMinuteSelect, endTimeHidden);
        });
    }
});

// Empêcher la soumission si des fichiers sont trop volumineux
document.getElementById('election-form').addEventListener('submit', function(e) {
    let hasError = false;
    
    // Vérifier le logo
    const logoInput = document.getElementById('logo');
    if (logoInput.files.length > 0) {
        const logoSizeKB = logoInput.files[0].size / 1024;
        if (logoSizeKB > 2048) {
            e.preventDefault();
            window.showAppPopup('error', 'Le logo est trop volumineux. Taille maximale: 2 Mo (2048 KB).', 'Erreur');
            hasError = true;
        }
    }
    
    // Vérifier les photos des candidats
    const photoInputs = document.querySelectorAll('.candidate-photo');
    photoInputs.forEach(input => {
        if (input.files.length > 0) {
            const photoSizeKB = input.files[0].size / 1024;
            if (photoSizeKB > 2048) {
                e.preventDefault();
                if (!hasError) {
                    window.showAppPopup('error', 'Une ou plusieurs photos de candidats sont trop volumineuses. Taille maximale: 2 Mo (2048 KB).', 'Erreur');
                }
                hasError = true;
            }
        }
    });
    
    if (hasError) {
        return false;
    }
});
</script>
@endpush
@endsection

