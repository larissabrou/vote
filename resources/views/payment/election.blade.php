@extends('layouts.app')

@section('title', isset($election) ? 'Paiement - Activer l\'élection' : 'Paiement')

@section('content')
<div class="min-h-[calc(100vh-3.5rem)] flex items-center justify-center py-6 sm:py-12 px-3 sm:px-6">
    {{-- Popup d'attente du paiement (loader + syntaxe opérateur) --}}
    <div id="payment-waiting-popup" class="fixed inset-0 z-[100] p-4 bg-black/50 backdrop-blur-sm" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 sm:p-8 text-center animate-[fadeIn_0.2s_ease-out]">
            <div class="flex justify-center mb-6">
                <svg class="animate-spin h-14 w-14 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">En attente du paiement</h3>
            <p id="payment-waiting-syntax" class="text-gray-600 text-sm sm:text-base mb-2"></p>
            <p class="text-gray-500 text-sm">Ne fermez pas cette page. Vérification en cours...</p>
        </div>
    </div>

    {{-- Popup d'erreur --}}
    <div id="payment-error-popup" class="fixed inset-0 z-[100] p-4 bg-black/50 backdrop-blur-sm" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 sm:p-8 text-center animate-[fadeIn_0.2s_ease-out]">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Erreur</h3>
            <p id="payment-error-popup-text" class="text-gray-600 text-sm sm:text-base mb-6"></p>
            <button type="button" id="payment-error-popup-close" class="btn btn-primary w-full sm:w-auto">Fermer</button>
        </div>
    </div>

    <div id="payment-status-error" class="max-w-md w-full hidden">
        <div class="card p-6 border-2 border-red-200 bg-red-50 text-center">
            <p class="font-semibold text-red-800" id="payment-status-error-text">Le paiement a échoué.</p>
            <a href="{{ isset($election) ? route('elections.voir', $election) : route('elections.liste') }}" class="btn btn-primary mt-4">Retour</a>
        </div>
    </div>

    <div id="payment-form-container" class="max-w-md w-full space-y-6 mx-auto">
        <div class="card p-5 sm:p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl flex items-center justify-center shadow-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold gradient-text">Paiement Mobile Money</h1>
                <p class="mt-2 text-gray-600">Payez <strong>{{ number_format($amount, 0, ',', ' ') }} {{ $currency }}</strong> via Mobile Money.</p>
            </div>

            <div class="bg-primary-50 border-2 border-primary-200 rounded-xl p-4 mb-6">
                @if(isset($election))
                    <p class="text-sm text-gray-700">
                        Pour activer l'élection <strong>{{ $election->title }}</strong>, payez <strong>{{ number_format($amount, 0, ',', ' ') }} FCFA</strong>. Les liens de vote seront envoyés par email aux votants.
                    </p>
                @else
                    <p class="text-sm text-gray-700">
                        Un paiement de <strong>{{ number_format($amount, 0, ',', ' ') }} FCFA</strong> est requis.
                    </p>
                @endif
            </div>

            @if(isset($emailsWithInvalidDomain) && count($emailsWithInvalidDomain) > 0)
            <div class="bg-amber-50 border-2 border-amber-300 rounded-xl p-4 mb-6">
                <p class="text-sm font-semibold text-amber-900 flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Vérification des adresses email
                </p>
                <p class="text-sm text-amber-800 mt-2">
                    <strong>{{ count($emailsWithInvalidDomain) }}</strong> adresse(s) ont un domaine sans serveur mail (MX) valide. Ces votants risquent de ne pas recevoir le lien.
                </p>
                <p class="text-sm text-amber-800 mt-1">
                    Vérifiez les fautes de frappe (ex. gmal.com au lieu de gmail.com) ou <a href="{{ route('elections.modifier', $election) }}" class="underline font-semibold">modifiez l'élection</a> pour corriger le fichier Excel avant de payer.
                </p>
            </div>
            @endif

            <form id="payment-form" class="space-y-4">
                @csrf
                <input type="hidden" name="payment_method" id="payment_method" value="">

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Choisir l'opérateur *</label>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($paymentMethods as $key => $method)
                            <button type="button"
                                class="operator-btn flex flex-col items-center justify-center p-4 rounded-xl border-2 border-gray-200 bg-white hover:border-primary-400 hover:bg-primary-50/50 transition-all text-center min-h-[80px]"
                                data-method="{{ $key }}"
                                data-syntax="{{ e($method['syntax']) }}"
                                data-validation-syntax="{{ e($method['validation_syntax'] ?? $method['syntax'] ?? '') }}"
                                data-fields="{{ json_encode($method['fields']) }}"
                                data-placeholder="{{ e($method['placeholder'] ?? '') }}">
                                <img src="{{ asset($method['image'] ?? '') }}" alt="{{ $method['label'] }}" class="h-10 w-10 object-cover rounded-full mb-2 mx-auto">
                                <span class="text-sm font-medium text-gray-800">{{ $method['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div id="operator-syntax-block" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-gray-700">
                    <p class="font-semibold mb-2">Syntaxe de validation :</p>
                    <p id="operator-syntax" class="mb-0"></p>
                </div>

                <div id="payment-fields" class="space-y-4 hidden">
                    <div class="form-group" id="field-phone-wrap">
                        <label for="phone">Numéro Mobile Money *</label>
                        <input type="tel" name="phone" id="phone" class="form-control" placeholder="" required>
                    </div>
                    <div class="form-group hidden" id="field-otp-wrap">
                        <label for="otp">Code OTP reçu par SMS *</label>
                        <input type="text" name="otp" id="otp" class="form-control" placeholder="Ex: 123456" maxlength="8" autocomplete="one-time-code">
                        <p id="otp-hint" class="mt-2 text-sm text-gray-600 hidden">Composez <code class="bg-amber-100 px-1 py-0.5 rounded">#144*82#</code> pour générer un code OTP.</p>
                    </div>
                </div>

                <div id="payment-error" class="hidden text-red-600 text-sm"></div>
                <button type="submit" class="btn btn-primary w-full hidden" id="btn-submit">
                    <span class="btn-text">Payer {{ number_format($amount, 0, ',', ' ') }} FCFA</span>
                    <span class="btn-loading items-center justify-center gap-2" style="display: none;">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="btn-loading-text">Envoi en cours...</span>
                    </span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('elections.liste') }}" class="text-sm text-primary-600 hover:text-primary-800 font-medium">← Retour aux élections</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const form = document.getElementById('payment-form');
    const paymentMethodInput = document.getElementById('payment_method');
    const syntaxBlock = document.getElementById('operator-syntax-block');
    const syntaxEl = document.getElementById('operator-syntax');
    const fieldsBlock = document.getElementById('payment-fields');
    const phoneWrap = document.getElementById('field-phone-wrap');
    const otpWrap = document.getElementById('field-otp-wrap');
    const phoneInput = document.getElementById('phone');
    const otpInput = document.getElementById('otp');
    const submitBtn = document.getElementById('btn-submit');
    const errEl = document.getElementById('payment-error');
    const waitingPopup = document.getElementById('payment-waiting-popup');
    const waitingSyntaxEl = document.getElementById('payment-waiting-syntax');
    const errorPopup = document.getElementById('payment-error-popup');
    const errorPopupText = document.getElementById('payment-error-popup-text');
    const errorPopupClose = document.getElementById('payment-error-popup-close');

    let currentValidationSyntax = '';

    function showWaitingPopup(syntaxText) {
        if (waitingSyntaxEl) waitingSyntaxEl.textContent = syntaxText || 'Validez le paiement sur votre téléphone.';
        if (waitingPopup) {
            waitingPopup.style.display = 'flex';
            waitingPopup.classList.add('items-center', 'justify-center');
        }
    }
    function hideWaitingPopup() {
        if (waitingPopup) {
            waitingPopup.style.display = 'none';
        }
    }
    function showErrorPopup(message) {
        if (errorPopupText) errorPopupText.textContent = message || 'Une erreur est survenue.';
        if (errorPopup) {
            errorPopup.style.display = 'flex';
            errorPopup.classList.add('items-center', 'justify-center');
        }
    }
    function hideErrorPopup() {
        if (errorPopup) {
            errorPopup.style.display = 'none';
        }
    }
    function resetSubmitButton() {
        submitBtn.disabled = false;
        var btnText = submitBtn.querySelector('.btn-text');
        var btnLoading = submitBtn.querySelector('.btn-loading');
        if (btnText) { btnText.classList.remove('hidden'); btnText.style.display = ''; }
        if (btnLoading) { btnLoading.style.display = 'none'; }
    }

    if (errorPopupClose) {
        errorPopupClose.addEventListener('click', function() {
            hideErrorPopup();
            resetSubmitButton();
        });
    }

    document.querySelectorAll('.operator-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const method = this.dataset.method;
            const syntax = this.dataset.syntax;
            currentValidationSyntax = this.dataset.validationSyntax || syntax || '';
            const fields = JSON.parse(this.dataset.fields || '[]');
            const placeholder = this.dataset.placeholder || '';

            document.querySelectorAll('.operator-btn').forEach(function(b) { b.classList.remove('border-primary-500', 'bg-primary-100'); b.classList.add('border-gray-200'); });
            this.classList.remove('border-gray-200');
            this.classList.add('border-primary-500', 'bg-primary-100');

            paymentMethodInput.value = method;
            syntaxBlock.classList.add('hidden');
            fieldsBlock.classList.remove('hidden');
            submitBtn.classList.remove('hidden');

            phoneWrap.classList.toggle('hidden', !fields.includes('phone'));
            otpWrap.classList.toggle('hidden', !fields.includes('otp'));
            var otpHint = document.getElementById('otp-hint');
            if (otpHint) otpHint.classList.toggle('hidden', method !== 'orange_money');
            phoneInput.placeholder = placeholder;
            phoneInput.required = fields.includes('phone');
            otpInput.required = fields.includes('otp');
            if (!fields.includes('otp')) otpInput.value = '';
            errEl.classList.add('hidden');
            errEl.textContent = '';
            hideErrorPopup();
        });
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!paymentMethodInput.value) {
            showErrorPopup('Veuillez choisir un opérateur.');
            return;
        }
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');
        errEl.classList.add('hidden');
        errEl.textContent = '';
        submitBtn.disabled = true;
        btnText.classList.add('hidden');
        btnText.style.display = 'none';
        btnLoading.style.display = 'flex';

        // MTN et Moov : afficher tout de suite le modal loader avec la syntaxe à composer
        const method = paymentMethodInput.value;
        if (method === 'mtn_momo' || method === 'moov') {
            showWaitingPopup('Validez le paiement en composant sur votre téléphone : ' + (currentValidationSyntax || 'la syntaxe indiquée par l\'opérateur.'));
        }

        const payload = {
            phone: phoneInput.value,
            payment_method: paymentMethodInput.value,
            election_id: {{ isset($election) ? $election->id : 'null' }}
        };
        if (otpWrap && !otpWrap.classList.contains('hidden') && otpInput.value) {
            payload.otp = otpInput.value;
        }

        try {
            const response = await fetch('{{ route("paiement.initier") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await response.json().catch(function() { return {}; });

            if (data.error) {
                hideWaitingPopup();
                showErrorPopup(data.error);
                resetSubmitButton();
                return;
            }

            if (data.payment_completed && data.redirect_url) {
                hideWaitingPopup();
                window.location.href = data.redirect_url || '{{ route("elections.liste") }}?payment_success=1';
                return;
            }
            if (data.payment_id && data.redirect_url) {
                if (data.wave_launch_url) {
                    hideWaitingPopup();
                    window.location.href = data.wave_launch_url;
                    return;
                }
                // Modal d'attente uniquement pour MTN et Moov ; pour Orange pas de modal, juste le bouton en chargement
                if (method === 'mtn_momo' || method === 'moov') {
                    // modal déjà affiché au clic
                } else {
                    hideWaitingPopup();
                    var loadingTextEl = document.getElementById('btn-loading-text');
                    if (loadingTextEl) loadingTextEl.textContent = 'Vérification du paiement...';
                }
                pollPaymentStatus(data.payment_id);
                return;
            }
        } catch (err) {
            hideWaitingPopup();
            showErrorPopup('Erreur de connexion. Réessayez.');
            resetSubmitButton();
        }
    });

    function pollPaymentStatus(paymentId) {
        const pollUrl = '{{ url("/paiement/confirmer") }}/' + paymentId + '/statut';
        const pollInterval = 3000;
        const maxAttempts = 120;
        let attempts = 0;

        function poll() {
            attempts++;
            fetch(pollUrl, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'completed') {
                    hideWaitingPopup();
                    window.location.href = data.redirect_url || '{{ route("elections.liste") }}?payment_success=1';
                    return;
                }
                if (data.status === 'failed') {
                    hideWaitingPopup();
                    showErrorPopup(data.message || 'Le paiement a échoué. Veuillez réessayer.');
                    resetSubmitButton();
                    return;
                }
                if (attempts < maxAttempts) {
                    setTimeout(poll, pollInterval);
                } else {
                    hideWaitingPopup();
                    showErrorPopup('Délai dépassé. Si le paiement a été effectué, vérifiez l\'élection. Sinon, réessayez.');
                    resetSubmitButton();
                }
            })
            .catch(function() {
                if (attempts < maxAttempts) {
                    setTimeout(poll, pollInterval);
                } else {
                    hideWaitingPopup();
                    showErrorPopup('Erreur de vérification. Réessayez.');
                    resetSubmitButton();
                }
            });
        }
        setTimeout(poll, pollInterval);
    }
})();
</script>
@endpush
@endsection
