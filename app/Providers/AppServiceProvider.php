<?php

namespace App\Providers;

use App\Services\AfribapayService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AfribapayService::class, function ($app) {
            $config = config('afribapay');
            // notify_url : URL de webhook appelée par Afribapay pour notifier le statut (comme dans l'exemple payin)
            $notifyUrl = $config['notify_url'] ?: url()->route('paiement.webhook.afribapay');
            return new AfribapayService(
                $config['base_url'],
                $config['merchant_key'],
                $notifyUrl,
                $config['token_url'] ?? null,
                $config['client_id'] ?? null,
                $config['client_secret'] ?? null,
                $config['country'],
                $config['lang'],
                $config['token_basic'] ?? null,
                $config['callback_base_url'] ?? null
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Évite l'erreur "La clé est trop longue" avec MySQL (index limité à 1000 octets en utf8mb4)
        Schema::defaultStringLength(191);

        // Lien de réinitialisation du mot de passe vers la zone admin
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return url(route('administration.mot-de-passe.reinitialiser', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        });
    }
}

