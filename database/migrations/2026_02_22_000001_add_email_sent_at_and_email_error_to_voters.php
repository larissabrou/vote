<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->timestamp('email_sent_at')->nullable()->after('token');
            $table->string('email_error', 500)->nullable()->after('email_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->dropColumn(['email_sent_at', 'email_error']);
        });
    }
};
