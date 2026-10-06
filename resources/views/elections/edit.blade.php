@extends('layouts.app')

@section('title', 'Modifier l\'élection')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 sm:space-y-8 px-2 sm:px-0">
    <div class="text-center mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-4xl md:text-5xl font-extrabold gradient-text mb-3">Modifier l'élection</h1>
        <p class="text-gray-600 text-base sm:text-lg">Modifiez les informations ci-dessous. Vous pouvez modifier une élection non payée ou une élection où personne n'a encore voté.</p>
    </div>

    <form action="{{ route('elections.mettre-a-jour', $election) }}" method="POST" enctype="multipart/form-data" id="election-form" class="space-y-6 sm:space-y-8">
        @csrf
        @method('PUT')

        <div class="card p-4 sm:p-6 lg:p-8 space-y-6">
            <h2 class="text-xl sm:text-2xl font-bold gradient-text flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Informations générales
            </h2>
            
            <div class="form-group">
                <label for="title">Titre de l'élection *</label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $election->title) }}" required>
            </div>

            <div class="form-group">
                <label for="logo" class="block mb-1">Logo de l'élection (max 2 Mo)</label>
                @if($election->logo)
                    <div class="mb-2">
                        <img src="{{ $election->logo_url }}" alt="Logo actuel" class="h-16 w-auto object-contain rounded">
                        <p class="text-sm text-gray-500 mt-1">Logo actuel. Laissez vide pour conserver.</p>
                    </div>
                @endif
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
                    @php
                        $electionDateVal = old('election_date', $election->election_date->format('d/m/Y'));
                        if ($electionDateVal && preg_match('/^\d{4}-\d{2}-\d{2}$/', $electionDateVal)) {
                            $electionDateVal = \Carbon\Carbon::parse($electionDateVal)->format('d/m/Y');
                        }
                    @endphp
                    <input type="text" name="election_date" id="election_date" class="form-control" value="{{ $electionDateVal }}" placeholder="jj/mm/aaaa" maxlength="10" required autocomplete="off">
                    <small class="text-gray-500 text-sm mt-1 block">Format : jj/mm/aaaa</small>
                </div>
                <div class="form-group">
                    <label for="end_date">Date de fin (optionnel – plusieurs jours)</label>
                    @php
                        $endDateVal = old('end_date', $election->end_date?->format('d/m/Y'));
                        if ($endDateVal && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDateVal)) {
                            $endDateVal = \Carbon\Carbon::parse($endDateVal)->format('d/m/Y');
                        }
                    @endphp
                    <input type="text" name="end_date" id="end_date" class="form-control" value="{{ $endDateVal }}" placeholder="jj/mm/aaaa" maxlength="10" autocomplete="off">
                    <small class="text-gray-500 text-sm mt-1 block">Laissez vide pour un seul jour.</small>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="start_time">Heure de début *</label>
                    @php
                        $startTime = old('start_time', $election->start_time);
                        $startHour = $startTime ? explode(':', $startTime)[0] : '';
                        $startMinute = $startTime && isset(explode(':', $startTime)[1]) ? explode(':', $startTime)[1] : '';
                    @endphp
                    <div class="flex gap-2 items-center">
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
                        <input type="hidden" name="start_time" id="start_time" value="{{ old('start_time', $election->start_time) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label for="end_time">Heure de fin *</label>
                    @php
                        $endTime = old('end_time', $election->end_time);
                        $endHour = $endTime ? explode(':', $endTime)[0] : '';
                        $endMinute = $endTime && isset(explode(':', $endTime)[1]) ? explode(':', $endTime)[1] : '';
                    @endphp
                    <div class="flex gap-2 items-center">
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
                        <input type="hidden" name="end_time" id="end_time" value="{{ old('end_time', $election->end_time) }}">
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
                    $candidatesData = session('candidates_data', null);
                    if ($candidatesData === null) {
                        $candidatesData = $election->candidates->map(fn($c) => [
                            'first_name' => $c->first_name,
                            'last_name' => $c->last_name,
                            'position' => $c->position,
                        ])->toArray();
                    }
                    if (empty($candidatesData)) {
                        $candidatesData = [['first_name' => '', 'last_name' => '', 'position' => '']];
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
                        @if(isset($election->candidates[$index]) && $election->candidates[$index]->photo)
                            <div class="mb-2">
                                <img src="{{ asset($election->candidates[$index]->photo) }}" alt="Photo actuelle" class="h-20 w-20 rounded-full object-cover">
                                <p class="text-sm text-gray-500 mt-1">Photo actuelle. Laissez vide pour conserver.</p>
                            </div>
                        @endif
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Liste des votants
            </h2>
            <div class="form-group">
                <label for="voters_file" class="block mb-1">Remplacer la liste des votants (optionnel)</label>
                <div class="file-input-fr flex items-center gap-2 flex-wrap mt-1">
                    <input type="file" name="voters_file" id="voters_file" class="file-input-hidden" accept=".xlsx,.xls" aria-label="Choisir un fichier">
                    <label for="voters_file" class="btn btn-parcourir cursor-pointer mb-0">Parcourir</label>
                    <span class="file-name text-gray-500 text-sm">Aucun fichier sélectionné</span>
                </div>
                <small class="text-gray-500 text-sm mt-1 block">Laissez vide pour conserver les {{ $election->voters->count() }} votant(s) actuels. Format: email, nom, prenom, nombre_voix</small>
            </div>
        </div>

        <div class="flex gap-4 justify-end">
            <a href="{{ route('elections.voir', $election) }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
        </div>
    </form>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.file-size-warning { @apply text-yellow-600; }
.file-size-error { @apply text-red-600; }
.file-input-hidden {
    position: absolute !important; width: 0.01px !important; height: 0.01px !important;
    opacity: 0 !important; overflow: hidden !important; clip: rect(0, 0, 0, 0) !important;
    pointer-events: none;
}
.file-input-fr { position: relative; }
.file-input-fr label[for]:not([class*="block"]) { pointer-events: auto; }
.btn-parcourir { @apply bg-white text-primary-600 border-2 border-primary-500 font-semibold; }
.btn-parcourir:hover { @apply bg-primary-50 text-primary-700 border-primary-600; }
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
let candidateIndex = {{ count($candidatesData) }};

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

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

function updateFileNameLabel(input) {
    const wrapper = input.closest('.file-input-fr');
    if (!wrapper) return;
    const nameEl = wrapper.querySelector('.file-name');
    if (nameEl) nameEl.textContent = input.files && input.files.length > 0 ? input.files[0].name : 'Aucun fichier sélectionné';
}

document.getElementById('logo')?.addEventListener('change', function() {
    checkFileSize(this, 2048);
    updateFileNameLabel(this);
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('candidate-photo')) {
        checkFileSize(e.target, 2048);
        updateFileNameLabel(e.target);
    }
});

document.getElementById('voters_file')?.addEventListener('change', function() {
    updateFileNameLabel(this);
});

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
    newCandidate.querySelector('.remove-candidate').addEventListener('click', function() {
        newCandidate.remove();
        updateCandidateNumbers();
    });
    const photoInput = newCandidate.querySelector('.candidate-photo');
    if (photoInput) photoInput.addEventListener('change', function() { checkFileSize(this, 2048); updateFileNameLabel(this); });
});

function updateCandidateNumbers() {
    const candidates = document.querySelectorAll('.candidate-item');
    candidates.forEach((candidate, index) => {
        candidate.setAttribute('data-index', index);
        const h3 = candidate.querySelector('h3');
        if (h3) {
            const badge = h3.querySelector('span');
            if (badge) badge.textContent = index + 1;
            const textParts = h3.childNodes;
            for (let i = 0; i < textParts.length; i++) {
                if (textParts[i].nodeType === Node.TEXT_NODE && textParts[i].textContent.trim().startsWith('Candidat')) {
                    textParts[i].textContent = ` Candidat ${index + 1}`;
                    break;
                }
            }
        }
        candidate.querySelectorAll('input[type="text"]').forEach(input => {
            if (input.name.includes('candidates[')) input.name = input.name.replace(/candidates\[\d+\]/, `candidates[${index}]`);
        });
        const fileInput = candidate.querySelector('input[type="file"]');
        if (fileInput) {
            fileInput.name = fileInput.name.replace(/candidates\[\d+\]/, `candidates[${index}]`);
            fileInput.setAttribute('data-index', index);
            const sizeInfo = candidate.querySelector('.file-size-info');
            if (sizeInfo) sizeInfo.id = `size-info-${index}`;
        }
    });
}

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

function updateTimeField(hourSelect, minuteSelect, hiddenInput) {
    const hour = hourSelect.value;
    const minute = minuteSelect.value;
    hiddenInput.value = (hour && minute) ? `${hour.padStart(2, '0')}:${minute.padStart(2, '0')}` : '';
}

document.addEventListener('DOMContentLoaded', function() {
    const startHourSelect = document.getElementById('start_time_hour');
    const startMinuteSelect = document.getElementById('start_time_minute');
    const startTimeHidden = document.getElementById('start_time');
    if (startHourSelect && startMinuteSelect && startTimeHidden) {
        if (startTimeHidden.value) {
            const [hour, minute] = startTimeHidden.value.split(':');
            if (hour && minute) {
                startHourSelect.value = hour.padStart(2, '0');
                startMinuteSelect.value = minute.padStart(2, '0');
            }
        }
        startHourSelect.addEventListener('change', () => updateTimeField(startHourSelect, startMinuteSelect, startTimeHidden));
        startMinuteSelect.addEventListener('change', () => updateTimeField(startHourSelect, startMinuteSelect, startTimeHidden));
    }
    const endHourSelect = document.getElementById('end_time_hour');
    const endMinuteSelect = document.getElementById('end_time_minute');
    const endTimeHidden = document.getElementById('end_time');
    if (endHourSelect && endMinuteSelect && endTimeHidden) {
        if (endTimeHidden.value) {
            const [hour, minute] = endTimeHidden.value.split(':');
            if (hour && minute) {
                endHourSelect.value = hour.padStart(2, '0');
                endMinuteSelect.value = minute.padStart(2, '0');
            }
        }
        endHourSelect.addEventListener('change', () => updateTimeField(endHourSelect, endMinuteSelect, endTimeHidden));
        endMinuteSelect.addEventListener('change', () => updateTimeField(endHourSelect, endMinuteSelect, endTimeHidden));
    }
});

document.getElementById('election-form').addEventListener('submit', function(e) {
    const logoInput = document.getElementById('logo');
    if (logoInput.files.length > 0 && logoInput.files[0].size / 1024 > 2048) {
        e.preventDefault();
        window.showAppPopup('error', 'Le logo est trop volumineux. Taille maximale: 2 Mo (2048 KB).', 'Erreur');
        return false;
    }
    document.querySelectorAll('.candidate-photo').forEach(input => {
        if (input.files.length > 0 && input.files[0].size / 1024 > 2048) {
            e.preventDefault();
            window.showAppPopup('error', 'Une ou plusieurs photos de candidats sont trop volumineuses. Taille maximale: 2 Mo (2048 KB).', 'Erreur');
            return false;
        }
    });
});
</script>
@endpush
@endsection
