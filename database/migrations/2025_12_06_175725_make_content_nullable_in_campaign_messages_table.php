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
        Schema::table('campaign_messages', function (Blueprint $table) {
            // Rendre content nullable (cette colonne n'est plus utilisée)
            $table->text('content')->nullable()->change();

            // Rendre message non nullable (c'est la colonne utilisée maintenant)
            $table->text('message')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_messages', function (Blueprint $table) {
            // Revenir à l'état précédent
            $table->text('content')->nullable(false)->change();
            $table->text('message')->nullable()->change();
        });
    }
};
