<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('model_type');
            $table->uuid('model_id');
            $table->string('old_status');
            $table->string('new_status');
            $table->text('reason')->nullable();
            $table->uuid('user_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Index pour les performances
            $table->index(['model_type', 'model_id']);
            $table->index(['model_type', 'model_id', 'created_at']);
            $table->index(['old_status', 'new_status']);
            $table->index(['user_id']);
            $table->index(['created_at']);

            // Clé étrangère pour l'utilisateur
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_histories');
    }
};
