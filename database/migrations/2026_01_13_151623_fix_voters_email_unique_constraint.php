<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            // Supprimer la contrainte unique sur email seul
            $table->dropUnique(['email']);
            
            // Ajouter une contrainte unique composite sur (election_id, email)
            // Cela permet à un même email d'exister dans plusieurs élections,
            // mais pas deux fois dans la même élection
            $table->unique(['election_id', 'email'], 'voters_election_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            // Supprimer la contrainte unique composite
            $table->dropUnique('voters_election_email_unique');
            
            // Restaurer la contrainte unique sur email seul
            $table->unique('email');
        });
    }
};
