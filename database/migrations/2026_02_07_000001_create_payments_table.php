<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('amount'); // 5000 FCFA
            $table->string('currency', 10)->default('XOF');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('phone', 20)->nullable();
            $table->string('reference', 64)->nullable()->unique();
            $table->string('payment_method', 32)->default('mobile_money');
            $table->foreignId('election_id')->nullable()->constrained('elections')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
