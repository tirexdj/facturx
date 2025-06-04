<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Nouveau token de consultation plus sécurisé
            $table->string('consultation_token', 64)->nullable()->after('pdf_path');
            
            // Champs pour la phase 6
            $table->boolean('is_purchase_order')->default(false)->after('status');
            $table->boolean('is_billable')->default(false)->after('is_purchase_order');
            $table->decimal('deposit_percentage', 5, 2)->nullable()->after('total_gross');
            $table->decimal('deposit_amount', 15, 5)->nullable()->after('deposit_percentage');
            $table->text('payment_terms')->nullable()->after('terms');
            $table->string('template_name')->nullable()->after('footer');
            $table->json('template_config')->nullable()->after('template_name');
            $table->json('legal_mentions')->nullable()->after('template_config');
            $table->text('internal_notes')->nullable()->after('legal_mentions');
            $table->text('public_notes')->nullable()->after('internal_notes');
            
            // Index pour améliorer les performances
            $table->index(['company_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['validity_date']);
            $table->index(['consultation_token']);
            $table->index(['is_purchase_order']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status']);
            $table->dropIndex(['client_id', 'status']);
            $table->dropIndex(['validity_date']);
            $table->dropIndex(['consultation_token']);
            $table->dropIndex(['is_purchase_order']);
            $table->dropIndex(['created_at']);
            
            $table->dropColumn([
                'consultation_token',
                'is_purchase_order',
                'is_billable',
                'deposit_percentage',
                'deposit_amount',
                'payment_terms',
                'template_name',
                'template_config',
                'legal_mentions',
                'internal_notes',
                'public_notes',
            ]);
        });
    }
};
