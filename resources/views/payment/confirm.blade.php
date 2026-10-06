@extends('layouts.app')

@section('title', 'Confirmer le paiement')

@section('content')
<div class="min-h-[calc(100vh-3.5rem)] flex items-center justify-center py-6 sm:py-12 px-3 sm:px-6">
    <div id="payment-status-loader" class="max-w-md w-full text-center py-12 hidden">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 border-2 border-amber-300 mb-4">
            <svg class="animate-spin h-8 w-8 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <p class="text-lg font-semibold text-gray-700">Vérification du paiement en cours...</p>
        <p class="text-sm text-gray-500 mt-1">Veuillez valider sur votre téléphone. Vérification automatique pendant 30 secondes.</p>
    </div>

    <div id="payment-confirm-content" class="max-w-md w-full space-y-6 mx-auto">
        <div class="card p-5 sm:p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-xl">
                    <span class="text-3xl">📱</span>
                </div>
                <h1 class="text-2xl font-extrabold gradient-text">Instructions de paiement</h1>
                <p class="mt-2 text-gray-600">Effectuez le paiement sur votre téléphone puis confirmez ci-dessous.</p>
            </div>

            <div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-4 mb-6 space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Montant</span>
                    <span class="font-bold">{{ number_format($amount, 0, ',', ' ') }} {{ $currency }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Référence</span>
                    <span class="font-mono font-bold text-primary-600">{{ $payment->reference }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Numéro</span>
                    <span class="font-medium">{{ $payment->phone }}</span>
                </div>
            </div>

            @php $methods = \App\Models\Payment::getPaymentMethods(); $method = $methods[$payment->payment_method] ?? null; @endphp
            @if($method)
                <div class="mb-4 rounded-xl p-3 bg-gray-100 border border-gray-200 flex items-center gap-3">
                    @if(!empty($method['image']))
                        <img src="{{ asset($method['image']) }}" alt="{{ $method['label'] }}" class="h-10 w-10 object-cover rounded-full">
                    @endif
                    <span class="font-semibold text-gray-800">{{ $method['label'] ?? $payment->payment_method }}</span>
                </div>
            @endif
            @php
                $isOrange = $payment->payment_method === \App\Models\Payment::METHOD_ORANGE_MONEY;
                $isMtn = $payment->payment_method === \App\Models\Payment::METHOD_MTN_MOMO;
                $isMoov = $payment->payment_method === \App\Models\Payment::METHOD_MOOV;
            @endphp
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 text-sm text-gray-700">
                <p class="font-semibold mb-2">Sur votre téléphone :</p>
                @if($isOrange)
                    <p class="mb-2">Code OTP reçu par SMS.</p>
                    <p class="mb-2"><strong>Syntaxe de validation :</strong> composez <code class="bg-amber-100 px-1.5 py-0.5 rounded">#144*82#</code> pour générer un code OTP.</p>
                @elseif($isMtn || $isMoov)
                    <p class="mb-2">En attente de validation. Une syntaxe vous sera communiquée pour valider le paiement sur votre téléphone (MTN / Moov).</p>
                @else
                    <p class="mb-2">Suivez les instructions de votre opérateur pour valider le paiement.</p>
                @endif
                <ol class="list-decimal list-inside space-y-1 mt-3">
                    <li>Montant : <strong>{{ number_format($amount, 0, ',', ' ') }} {{ $currency }}</strong></li>
                    <li>Référence si demandée : <strong>{{ $payment->reference }}</strong></li>
                </ol>
            </div>

            <form action="{{ route('paiement.confirmer.simuler', $payment) }}" method="POST" class="space-y-4">
                @csrf
                <button type="submit" class="btn btn-primary w-full">
                    J'ai effectué le paiement
                </button>
            </form>

            <p class="mt-4 text-center text-xs text-gray-500">
                En production, la validation se fera automatiquement via Orange Money / MTN MoMo.
            </p>

            <div class="mt-6 text-center">
                @if($payment->election_id)
                    <a href="{{ route('elections.voir', $payment->election_id) }}" class="text-sm text-primary-600 hover:text-primary-800 font-medium">Retour à l'élection</a>
                @else
                    <a href="{{ route('elections.liste') }}" class="text-sm text-primary-600 hover:text-primary-800 font-medium">Retour aux élections</a>
                @endif
            </div>
        </div>
    </div>

    <div id="payment-status-error" class="max-w-md w-full hidden">
        <div class="card p-6 border-2 border-red-200 bg-red-50 text-center">
            <p class="font-semibold text-red-800" id="payment-status-error-text">Le paiement a échoué.</p>
            <a href="{{ $payment->election_id ? route('elections.voir', $payment->election_id) : route('elections.liste') }}" class="btn btn-primary mt-4">Retour</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const loaderEl = document.getElementById('payment-status-loader');
    const contentEl = document.getElementById('payment-confirm-content');
    const errorEl = document.getElementById('payment-status-error');
    const errorTextEl = document.getElementById('payment-status-error-text');
    const statusUrl = '{{ route('paiement.confirmer.statut', $payment) }}';
    const pollIntervalMs = 2000;
    const pollDurationMs = 30000;
    let pollStart = Date.now();
    let pollTimer = null;

    function showLoader() {
        if (loaderEl) loaderEl.classList.remove('hidden');
        if (contentEl) contentEl.classList.add('hidden');
        if (errorEl) errorEl.classList.add('hidden');
    }
    function showContent() {
        if (loaderEl) loaderEl.classList.add('hidden');
        if (contentEl) contentEl.classList.remove('hidden');
        if (errorEl) errorEl.classList.add('hidden');
    }
    function showError(message) {
        if (loaderEl) loaderEl.classList.add('hidden');
        if (contentEl) contentEl.classList.add('hidden');
        if (errorEl) {
            errorEl.classList.remove('hidden');
            if (errorTextEl) errorTextEl.textContent = message || 'Le paiement a échoué.';
        }
    }

    function stopPolling() {
        if (pollTimer) {
            clearTimeout(pollTimer);
            pollTimer = null;
        }
    }

    function checkStatus() {
        if (Date.now() - pollStart > pollDurationMs) {
            stopPolling();
            showContent();
            return;
        }
        fetch(statusUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'completed') {
                stopPolling();
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }
                showContent();
            } else if (data.status === 'failed') {
                stopPolling();
                showError(data.message || 'Le paiement a échoué.');
            } else {
                pollTimer = setTimeout(checkStatus, pollIntervalMs);
            }
        })
        .catch(function() {
            pollTimer = setTimeout(checkStatus, pollIntervalMs);
        });
    }

    showLoader();
    checkStatus();
})();
</script>
@endpush
@endsection
