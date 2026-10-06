<?php

use App\Http\Controllers\AfribapayWebhookController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ResultsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('accueil');

// Guide du vote (accessible à tous avant de voter)
Route::get('/guide-vote', function () {
    return view('guide-vote');
})->name('guide-vote');

// Résultats : accès par lien unique (token votant) uniquement — plus d'URL publique par ID
Route::get('/election/resultats/{token}', [ResultsController::class, 'showByToken'])->name('election.resultats.token');
Route::get('/election/resultats/{token}/api', [ResultsController::class, 'apiByToken'])->name('election.resultats.api.token');

// Routes d'authentification (email + mot de passe)
Route::prefix('administration')->name('administration.')->group(function () {
    Route::get('/connexion', [AdminAuthController::class, 'showLoginForm'])->name('connexion');
    Route::post('/connexion', [AdminAuthController::class, 'login'])->name('connexion.post');
    Route::get('/inscription', [AdminAuthController::class, 'showRegisterForm'])->name('inscription');
    Route::post('/inscription', [AdminAuthController::class, 'register'])->name('inscription.post');
    Route::post('/deconnexion', [AdminAuthController::class, 'logout'])->name('deconnexion');
    // Mot de passe oublié
    Route::get('/mot-de-passe/oublie', [AdminAuthController::class, 'showForgotPasswordForm'])->name('mot-de-passe.oublie');
    Route::post('/mot-de-passe/email', [AdminAuthController::class, 'sendResetLink'])->name('mot-de-passe.email');
    Route::get('/mot-de-passe/reinitialiser/{token}', [AdminAuthController::class, 'showResetPasswordForm'])->name('mot-de-passe.reinitialiser');
    Route::post('/mot-de-passe/reinitialiser', [AdminAuthController::class, 'resetPassword'])->name('mot-de-passe.mettre-a-jour');
});

// Webhook Afribapay (sans auth) — met à jour le statut du paiement en base (SUCCESS → completed + activation, FAILED/CANCELLED/EXPIRED → failed)
Route::post('/paiement/webhook/afribapay', [AfribapayWebhookController::class, 'notify'])->name('paiement.webhook.afribapay');
Route::post('/payment/webhook/afribapay', [AfribapayWebhookController::class, 'notify'])->name('payment.webhook.afribapay'); // alias pour vote.czotick.com

// Retour / annulation Wave (URLs appelées par Wave après paiement : ?transactionId= référence)
Route::prefix('paiement')->name('paiement.')->group(function () {
    Route::get('/retour/succes', [PaymentController::class, 'waveReturnSuccess'])->name('retour.succes');
    Route::get('/retour/echec', [PaymentController::class, 'waveReturnError'])->name('retour.echec');
    Route::get('/wave/retour/{payment}', [PaymentController::class, 'waveReturn'])->name('wave.retour');
    Route::get('/wave/annuler/{payment}', [PaymentController::class, 'waveCancel'])->name('wave.annuler');
});

// Paiement pour activer une élection (5000 FCFA) - après création
Route::prefix('paiement')->name('paiement.')->middleware('admin')->group(function () {
    Route::get('/election/{election}/activer', [PaymentController::class, 'showActivateElection'])->name('election.activer');
    Route::post('/initier', [PaymentController::class, 'initiate'])->name('initier');
    Route::get('/confirmer/{payment}', [PaymentController::class, 'confirm'])->name('confirmer');
    Route::get('/confirmer/{payment}/statut', [PaymentController::class, 'checkTransactionStatus'])->name('confirmer.statut');
    Route::post('/confirmer/{payment}/simuler', [PaymentController::class, 'confirmSimulate'])->name('confirmer.simuler');
});

// Routes élections (Admin uniquement) — routes fixes avant /{election}
Route::prefix('elections')->name('elections.')->middleware('admin')->group(function () {
    Route::get('/', [ElectionController::class, 'index'])->name('liste');
    Route::get('/creer', [ElectionController::class, 'create'])->name('creer');
    Route::get('/modele-votants', [ElectionController::class, 'downloadVotantsTemplate'])->name('template.votants');
    Route::post('/', [ElectionController::class, 'store'])->name('enregistrer');
    Route::get('/{election}', [ElectionController::class, 'show'])->name('voir');
    Route::get('/{election}/modifier', [ElectionController::class, 'edit'])->name('modifier');
    Route::put('/{election}', [ElectionController::class, 'update'])->name('mettre-a-jour');
    Route::get('/{election}/electeurs/export', [ElectionController::class, 'exportElecteursExcel'])->name('electeurs.export');
    Route::get('/{election}/electeurs', [ElectionController::class, 'voters'])->name('electeurs');
    Route::get('/{election}/resultats', [ResultsController::class, 'show'])->name('resultats');
    Route::get('/{election}/resultats/api', [ResultsController::class, 'api'])->name('resultats.api');
    Route::post('/{election}/activer', [ElectionController::class, 'activate'])->name('activer');
    Route::post('/{election}/renvoyer-liens', [ElectionController::class, 'resendVotingLinks'])->name('renvoyer-liens');
    Route::put('/{election}/voters/{voter}', [ElectionController::class, 'updateVoterEmail'])->name('voters.mettre-a-jour');
    Route::put('/{election}/voters/{voter}/voix', [ElectionController::class, 'updateVoterVoteCount'])->name('voters.mettre-a-jour-voix');
    Route::delete('/{election}/voters/{voter}', [ElectionController::class, 'destroyVoter'])->name('voters.supprimer');
    Route::post('/{election}/voters/{voter}/renvoyer-lien', [ElectionController::class, 'resendVotingLinkToOne'])->name('voters.renvoyer-lien');
});

// Inscription publique des votants (saisie email)
Route::get('/inscription-votant/{election}', [ElectionController::class, 'showPublicVoterRegistration'])->middleware('signed')->name('votants.inscription');
Route::post('/inscription-votant/{election}', [ElectionController::class, 'storePublicVoterRegistration'])->middleware('signed')->name('votants.inscription.enregistrer');

// Routes pour le vote (publiques avec token)
Route::prefix('voter')->name('voter.')->group(function () {
    Route::get('/authentifier/{token}', [VoteController::class, 'authenticate'])->name('authentifier');
    Route::get('/{electionId}', [VoteController::class, 'show'])->name('voir');
    Route::post('/{electionId}', [VoteController::class, 'submit'])->name('soumettre');
});

// Routes pour le tableau de bord (Admin uniquement)
Route::prefix('tableau-de-bord')->name('tableau-de-bord.')->middleware('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'show'])->name('voir');
    Route::get('/api/{electionId}', [DashboardController::class, 'api'])->name('api');
});
