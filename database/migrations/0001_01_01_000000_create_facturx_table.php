<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacturxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Création des indexes
        Schema::defaultStringLength(191);
        
        // Plans
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->nullable();
            $table->decimal('price_yearly', 10, 2)->nullable();
            $table->char('currency_code', 3)->default('EUR');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->integer('trial_days')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('code');
        });
        
        // Users
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('username')->nullable()->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->uuid('company_id')->nullable();
            $table->string('job_title')->nullable();
            $table->string('profile_picture_path')->nullable();
            $table->string('locale', 10)->default('fr');
            $table->string('timezone')->default('Europe/Paris');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->string('api_token', 80)->unique()->nullable();
            $table->rememberToken();
            $table->uuid('role_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('role_id');
            $table->index(['email', 'deleted_at']);
        });
        
        // Companies
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('trading_name')->nullable();
            $table->string('siren', 9)->nullable();
            $table->string('siret', 14)->nullable();
            $table->string('vat_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('legal_form')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->uuid('plan_id');
            $table->uuid('pdp_id')->nullable();
            $table->string('vat_regime')->nullable();
            $table->date('fiscal_year_start')->nullable();
            $table->char('currency_code', 3)->default('EUR');
            $table->char('language_code', 2)->default('fr');
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('plan_id');
            $table->index('pdp_id');
            $table->index('siren');
            $table->index('siret');
            $table->index(['name', 'deleted_at']);
            
            // Foreign keys
            $table->foreign('plan_id')->references('id')->on('plans');
        });
        
        // Ajout de la foreign key sur users
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
          $table->string('email')->primary();
          $table->string('token');
          $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
        
        // Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('name');
        });
        
        // Permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('group')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Index
            $table->index('key');
            $table->index('group');
        });
        
        // Role permissions
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->uuid('permission_id');
            $table->timestamps();
            $table->uuid('created_by')->nullable();
            
            // Primary key
            $table->primary(['role_id', 'permission_id']);
            
            // Foreign keys
            $table->foreign('role_id')->references('id')->on('roles');
            $table->foreign('permission_id')->references('id')->on('permissions');
        });
        
        // User permissions (exceptions)
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('permission_id');
            $table->boolean('granted')->default(true);
            $table->timestamps();
            $table->uuid('created_by')->nullable();
            
            // Primary key
            $table->primary(['user_id', 'permission_id']);
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('permission_id')->references('id')->on('permissions');
        });
        
        // Addresses (polymorphic)
        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('addressable_type');
            $table->uuid('addressable_id');
            $table->string('label')->nullable();
            $table->string('line_1');
            $table->string('line_2')->nullable();
            $table->string('line_3')->nullable();
            $table->string('postal_code', 20);
            $table->string('city');
            $table->string('state_province')->nullable();
            $table->char('country_code', 2)->default('FR');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_billing')->default(false);
            $table->boolean('is_shipping')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index(['addressable_type', 'addressable_id']);
            $table->index('postal_code');
            $table->index('city');
        });
        
        // Phone numbers (polymorphic)
        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phoneable_type');
            $table->uuid('phoneable_id');
            $table->string('label')->nullable();
            $table->string('country_code', 5)->default('+33');
            $table->string('number', 20);
            $table->string('extension', 10)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_mobile')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index(['phoneable_type', 'phoneable_id']);
            $table->index('number');
        });
        
        // Emails (polymorphic)
        Schema::create('emails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('emailable_type');
            $table->uuid('emailable_id');
            $table->string('label')->nullable();
            $table->string('email');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->string('verification_token')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index(['emailable_type', 'emailable_id']);
            $table->index('email');
        });
        
        // Clients
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->enum('client_type', ['company', 'individual'])->default('company');
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('trading_name')->nullable();
            $table->string('siren', 9)->nullable();
            $table->string('siret', 14)->nullable();
            $table->string('vat_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('legal_form')->nullable();
            $table->string('website')->nullable();
            $table->uuid('category_id')->nullable();
            $table->char('currency_code', 3)->default('EUR');
            $table->char('language_code', 2)->default('fr');
            $table->uuid('payment_terms_id')->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('category_id');
            $table->index('payment_terms_id');
            $table->index('siren');
            $table->index('siret');
            $table->index(['name', 'deleted_at']);
            $table->index(['tags']);
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
        });
        
        // Contacts
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('contactable_type');
            $table->uuid('contactable_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index(['contactable_type', 'contactable_id']);
            $table->index(['first_name', 'last_name']);
        });
        
        // Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->string('slug');
            $table->uuid('parent_id')->nullable(); // Define the column
            $table->enum('type', ['product', 'service', 'client', 'expense'])->default('product');
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable();
            $table->string('icon')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();

            // Index
            $table->index('company_id');
            $table->index('parent_id'); // Index for the foreign key
            $table->index('slug');
            $table->index('type');

            // Foreign keys (excluding the self-referencing one for now)
            $table->foreign('company_id')->references('id')->on('companies');
        });
        
        // Units
        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->string('abbreviation', 10);
            $table->boolean('is_time_based')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
        });
        
        // VAT Rates
        Schema::create('vat_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->decimal('rate', 5, 2);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->char('country_code', 2)->default('FR');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('rate');
            $table->index('country_code');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
        });
        
        // Products
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('reference')->nullable();
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('long_description')->nullable();
            $table->decimal('price_net', 15, 5);
            $table->decimal('cost_price', 15, 5)->nullable();
            $table->uuid('vat_rate_id');
            $table->uuid('unit_id');
            $table->uuid('category_id')->nullable();
            $table->boolean('stock_management')->default(false);
            $table->decimal('stock_quantity', 15, 3)->default(0);
            $table->decimal('stock_alert_threshold', 15, 3)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->jsonb('dimensions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->jsonb('tags')->nullable();
            $table->jsonb('custom_fields')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('vat_rate_id');
            $table->index('unit_id');
            $table->index('category_id');
            $table->index('reference');
            $table->index('barcode');
            $table->index('slug');
            $table->index(['name', 'deleted_at']);
            $table->index(['tags']);
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('vat_rate_id')->references('id')->on('vat_rates');
            $table->foreign('unit_id')->references('id')->on('units');
            $table->foreign('category_id')->references('id')->on('categories');
        });
        
        // Product Attributes
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->enum('type', ['select', 'checkbox', 'radio', 'text', 'color'])->default('select');
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
        });
        
        // Product Attribute Values
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_attribute_id');
            $table->string('value');
            $table->string('color_code', 7)->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('product_attribute_id');
            
            // Foreign keys
            $table->foreign('product_attribute_id')->references('id')->on('product_attributes');
        });
        
        // Product Variants
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('price_net_modifier', 15, 5)->nullable();
            $table->decimal('price_net_override', 15, 5)->nullable();
            $table->decimal('stock_quantity', 15, 3)->default(0);
            $table->jsonb('attributes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('product_id');
            $table->index('sku');
            $table->index('barcode');
            $table->index(['attributes']);
            
            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products');
        });
        
        // Services
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('reference')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('long_description')->nullable();
            $table->decimal('price_net', 15, 5);
            $table->decimal('cost_price', 15, 5)->nullable();
            $table->uuid('vat_rate_id');
            $table->uuid('unit_id');
            $table->integer('duration')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_interval')->nullable();
            $table->uuid('category_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('tags')->nullable();
            $table->jsonb('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('vat_rate_id');
            $table->index('unit_id');
            $table->index('category_id');
            $table->index('reference');
            $table->index('slug');
            $table->index(['name', 'deleted_at']);
            $table->index(['tags']);
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('vat_rate_id')->references('id')->on('vat_rates');
            $table->foreign('unit_id')->references('id')->on('units');
            $table->foreign('category_id')->references('id')->on('categories');
        });

        Schema::table('categories', function (Blueprint $table) {
          $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null') // Optional: Consider ON DELETE behavior (e.g., set null, cascade)
                ->onUpdate('cascade'); // Optional: Consider ON UPDATE behavior
        });
        
        // Payment Terms
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->integer('days');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
        });
        
        // Payment Methods
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->enum('type', ['cash', 'bank_transfer', 'card', 'check', 'other'])->default('bank_transfer');
            $table->boolean('is_online')->default(false);
            $table->boolean('is_active')->default(true);
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('type');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
        });
        
        // Templates
        Schema::create('templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->enum('type', ['quote', 'invoice', 'email'])->default('invoice');
            $table->text('content');
            $table->text('css')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('thumbnail_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('type');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
        });
        
        // Quotes
        Schema::create('quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('client_id');
            $table->string('quote_number');
            $table->string('reference')->nullable();
            $table->string('title')->nullable();
            $table->text('introduction')->nullable();
            $table->date('date');
            $table->date('validity_date');
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->char('currency_code', 3)->default('EUR');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            $table->decimal('subtotal_net', 15, 5);
            $table->enum('discount_type', ['percent', 'amount'])->nullable();
            $table->decimal('discount_value', 15, 5)->nullable();
            $table->decimal('discount_amount', 15, 5)->nullable();
            $table->decimal('total_net', 15, 5);
            $table->decimal('total_tax', 15, 5);
            $table->decimal('total_gross', 15, 5);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('footer')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->jsonb('signature_data')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('client_id');
            $table->index('quote_number');
            $table->index('reference');
            $table->index('status');
            $table->index(['date', 'deleted_at']);
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('client_id')->references('id')->on('clients');
        });
        
        // Quote Lines
        Schema::create('quote_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('quote_id');
            $table->enum('line_type', ['product', 'service', 'text', 'section'])->default('product');
            $table->uuid('product_id')->nullable();
            $table->uuid('service_id')->nullable();
            $table->uuid('product_variant_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 5);
            $table->uuid('unit_id');
            $table->decimal('unit_price_net', 15, 5);
            $table->uuid('vat_rate_id');
            $table->enum('discount_type', ['percent', 'amount'])->nullable();
            $table->decimal('discount_value', 15, 5)->nullable();
            $table->decimal('discount_amount', 15, 5)->nullable();
            $table->decimal('subtotal_net', 15, 5);
            $table->decimal('tax_amount', 15, 5);
            $table->decimal('total_net', 15, 5);
            $table->integer('position')->default(0);
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('quote_id');
            $table->index('product_id');
            $table->index('service_id');
            $table->index('product_variant_id');
            $table->index('unit_id');
            $table->index('vat_rate_id');
            
            // Foreign keys
            $table->foreign('quote_id')->references('id')->on('quotes');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('service_id')->references('id')->on('services');
            $table->foreign('product_variant_id')->references('id')->on('product_variants');
            $table->foreign('unit_id')->references('id')->on('units');
            $table->foreign('vat_rate_id')->references('id')->on('vat_rates');
        });
        
        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('client_id');
            $table->uuid('quote_id')->nullable();
            $table->string('invoice_number');
            $table->string('reference')->nullable();
            $table->string('title')->nullable();
            $table->text('introduction')->nullable();
            $table->date('date');
            $table->date('due_date');
            $table->enum('status', ['draft', 'sent', 'paid', 'partial', 'overdue', 'cancelled'])->default('draft');
            $table->enum('e_invoice_status', ['pending', 'transmitted', 'rejected', 'accepted'])->nullable();
            $table->string('payment_status')->nullable();
            $table->char('currency_code', 3)->default('EUR');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            $table->decimal('subtotal_net', 15, 5);
            $table->enum('discount_type', ['percent', 'amount'])->nullable();
            $table->decimal('discount_value', 15, 5)->nullable();
            $table->decimal('discount_amount', 15, 5)->nullable();
            $table->decimal('total_net', 15, 5);
            $table->decimal('total_tax', 15, 5);
            $table->decimal('total_gross', 15, 5);
            $table->decimal('amount_paid', 15, 5)->default(0);
            $table->decimal('amount_due', 15, 5);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('footer')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->enum('e_invoice_format', ['UBL', 'CII', 'Factur-X'])->nullable();
            $table->jsonb('e_invoice_data')->nullable();
            $table->string('e_invoice_path')->nullable();
            $table->string('e_reporting_status')->nullable();
            $table->timestamp('e_reporting_transmitted_at')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->uuid('recurrence_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('client_id');
            $table->index('quote_id');
            $table->index('invoice_number');
            $table->index('reference');
            $table->index('status');
            $table->index('e_invoice_status');
            $table->index(['date', 'deleted_at']);
            $table->index('due_date');
            $table->index('recurrence_id');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('quote_id')->references('id')->on('quotes');
        });
        
        // Invoice Lines
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->enum('line_type', ['product', 'service', 'text', 'section'])->default('product');
            $table->uuid('product_id')->nullable();
            $table->uuid('service_id')->nullable();
            $table->uuid('product_variant_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 5);
            $table->uuid('unit_id');
            $table->decimal('unit_price_net', 15, 5);
            $table->uuid('vat_rate_id');
            $table->enum('discount_type', ['percent', 'amount'])->nullable();
            $table->decimal('discount_value', 15, 5)->nullable();
            $table->decimal('discount_amount', 15, 5)->nullable();
            $table->decimal('subtotal_net', 15, 5);
            $table->decimal('tax_amount', 15, 5);
            $table->decimal('total_net', 15, 5);
            $table->integer('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('invoice_id');
            $table->index('product_id');
            $table->index('service_id');
            $table->index('product_variant_id');
            $table->index('unit_id');
            $table->index('vat_rate_id');
            
            // Foreign keys
            $table->foreign('invoice_id')->references('id')->on('invoices');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('service_id')->references('id')->on('services');
            $table->foreign('product_variant_id')->references('id')->on('product_variants');
            $table->foreign('unit_id')->references('id')->on('units');
            $table->foreign('vat_rate_id')->references('id')->on('vat_rates');
        });
        
        // Invoice Recurrences
        Schema::create('invoice_recurrences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('client_id');
            $table->uuid('template_invoice_id');
            $table->string('name');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->integer('interval')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_date')->nullable();
            $table->integer('day_of_month')->nullable();
            $table->integer('month_of_year')->nullable();
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('client_id');
            $table->index('template_invoice_id');
            $table->index('status');
            $table->index('next_date');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('template_invoice_id')->references('id')->on('invoices');
        });
        
        // Add recurrence foreign key to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('recurrence_id')->references('id')->on('invoice_recurrences');
        });
        
        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('invoice_id');
            $table->date('date');
            $table->decimal('amount', 15, 5);
            $table->uuid('payment_method_id');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_reconciled')->default(false);
            $table->string('transaction_id')->nullable();
            $table->enum('status', ['completed', 'pending', 'failed', 'refunded'])->default('completed');
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('invoice_id');
            $table->index('payment_method_id');
            $table->index('date');
            $table->index('status');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('invoice_id')->references('id')->on('invoices');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods');
        });
        
        // E-Invoicing Logs
        Schema::create('e_invoicing_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('invoice_id');
            $table->string('status');
            $table->enum('log_type', ['transmission', 'status_update', 'error'])->default('transmission');
            $table->text('log_message')->nullable();
            $table->jsonb('request_data')->nullable();
            $table->jsonb('response_data')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_details')->nullable();
            $table->timestamps();
            $table->uuid('created_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('invoice_id');
            $table->index('status');
            $table->index('log_type');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('invoice_id')->references('id')->on('invoices');
        });
        
        // E-Reporting Transmissions
        Schema::create('e_reporting_transmissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->enum('transmission_type', ['B2C', 'B2B_international'])->default('B2C');
            $table->date('period_start');
            $table->date('period_end');
            $table->datetime('submission_date')->nullable();
            $table->enum('status', ['pending', 'transmitted', 'accepted', 'rejected'])->default('pending');
            $table->jsonb('data')->nullable();
            $table->jsonb('response_data')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('transmission_type');
            $table->index(['period_start', 'period_end']);
            $table->index('status');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
        });
        
        // PDP Configurations
        Schema::create('pdp_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('pdp_code');
            $table->string('pdp_name');
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->string('endpoint_url')->nullable();
            $table->jsonb('configuration')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('test_mode')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('pdp_code');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
        });
        
        // Features
        Schema::create('features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
            
            // Index
            $table->index('code');
            $table->index('category');
        });
        
        // Plan Features
        Schema::create('plan_features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('plan_id');
            $table->uuid('feature_id');
            $table->boolean('is_enabled')->default(true);
            $table->integer('value_limit')->nullable(); // null or -1 = illimité
            $table->timestamps();
            
            // Unique key
            $table->unique(['plan_id', 'feature_id']);
            
            // Index
            $table->index('plan_id');
            $table->index('feature_id');
            
            // Foreign keys
            $table->foreign('plan_id')->references('id')->on('plans');
            $table->foreign('feature_id')->references('id')->on('features');
        });
        
        // Feature Usage
        Schema::create('feature_usage', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('feature_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('count')->default(0);
            $table->datetime('last_updated')->nullable();
            $table->timestamps();
            
            // Unique key
            $table->unique(['company_id', 'feature_id', 'period_start', 'period_end']);
            
            // Index
            $table->index('company_id');
            $table->index('feature_id');
            $table->index(['period_start', 'period_end']);
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('feature_id')->references('id')->on('features');
        });
        
        // Activity Log
        Schema::create('activity_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('loggable_type');
            $table->uuid('loggable_id');
            $table->string('action');
            $table->jsonb('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            
            // Index
            $table->index('company_id');
            $table->index('user_id');
            $table->index(['loggable_type', 'loggable_id']);
            $table->index('action');
            $table->index('created_at');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Supprimer les tables dans l'ordre inverse pour respecter les contraintes de clés étrangères
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('feature_usage');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('features');
        Schema::dropIfExists('pdp_configurations');
        Schema::dropIfExists('e_reporting_transmissions');
        Schema::dropIfExists('e_invoicing_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices'); // D'abord suppression de la FK recurrence_id
        Schema::dropIfExists('invoice_recurrences');
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['recurrence_id']);
        });
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('quote_lines');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('payment_terms');
        Schema::dropIfExists('services');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('vat_rates');
        Schema::dropIfExists('units');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('emails');
        Schema::dropIfExists('phone_numbers');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });
        Schema::dropIfExists('companies');
        Schema::dropIfExists('users');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
}