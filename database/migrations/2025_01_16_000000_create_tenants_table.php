<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
      # Create tenants table for SAAS multi-tenancy

      1. New Tables
        - `tenants`
          - `id` (uuid, primary key)
          - `name` (string, nom de l'organisation)
          - `slug` (string, unique, pour sous-domaines)
          - `domain` (string, nullable, domaine personnalisé)
          - `plan` (enum, plan d'abonnement)
          - `status` (enum, statut du tenant)
          - `settings` (json, paramètres spécifiques)
          - `expires_at` (timestamp, expiration abonnement)
          - `created_at`, `updated_at`, `deleted_at`

      2. Security
        - Enable RLS-like behavior via model scopes
        - Add indexes for performance
    */

    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Nom de l'organisation
            $table->string('slug')->unique(); // Pour sous-domaines (ex: client1.monapp.com)
            $table->string('domain')->nullable(); // Domaine personnalisé
            
            // Plans SAAS
            $table->enum('plan', ['free', 'starter', 'professional', 'enterprise'])
                  ->default('free');
            
            // Statut
            $table->enum('status', ['active', 'suspended', 'cancelled'])
                  ->default('active');
            
            // Paramètres spécifiques au tenant
            $table->json('settings')->nullable();
            
            // Limites par plan
            $table->integer('max_users')->default(1);
            $table->integer('max_products')->default(100);
            $table->integer('max_orders_per_month')->default(50);
            
            // Abonnement
            $table->timestamp('expires_at')->nullable();
            $table->decimal('monthly_price', 8, 2)->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'plan']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};