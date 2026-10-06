<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voter_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voter_id')->constrained()->onDelete('cascade');
            $table->string('position'); // Le nom de la position/fonction (ex: "Président", "Vice-président")
            $table->integer('vote_count')->default(1); // Nombre de voix pour cette position
            $table->timestamps();
            
            // Un votant ne peut avoir qu'une seule entrée par position
            $table->unique(['voter_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_positions');
    }
};
