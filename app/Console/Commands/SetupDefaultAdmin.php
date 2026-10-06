<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupDefaultAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:setup {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure le mot de passe administrateur par défaut';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            $this->error('Le fichier .env n\'existe pas. Veuillez créer un fichier .env à partir de .env.example');
            return 1;
        }

        $envContent = File::get($envPath);
        
        // Générer un mot de passe par défaut sécurisé
        $defaultPassword = $this->option('password') ?? 'admin123';
        
        // Vérifier si ADMIN_PASSWORD existe déjà
        if (strpos($envContent, 'ADMIN_PASSWORD=') !== false) {
            // Remplacer la valeur existante
            $pattern = '/ADMIN_PASSWORD=.*/';
            $replacement = "ADMIN_PASSWORD={$defaultPassword}";
            $envContent = preg_replace($pattern, $replacement, $envContent);
            $this->info('Mot de passe administrateur mis à jour dans le fichier .env');
        } else {
            // Ajouter ADMIN_PASSWORD à la fin du fichier
            $envContent .= "\nADMIN_PASSWORD={$defaultPassword}\n";
            $this->info('Mot de passe administrateur ajouté au fichier .env');
        }
        
        File::put($envPath, $envContent);
        
        $this->newLine();
        $this->info('✅ Configuration de l\'administrateur terminée !');
        $this->newLine();
        $this->line('📋 Informations de connexion :');
        $this->line('   URL: ' . url('/admin/login'));
        $this->line('   Mot de passe: ' . $defaultPassword);
        $this->newLine();
        $this->warn('⚠️  Important: Changez ce mot de passe en production !');
        
        return 0;
    }
}
