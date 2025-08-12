<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
      # Add tenant_id to existing tables for multi-tenancy

      1. Changes
        - Add `tenant_id` to users, clients, categories, products, quotes, orders, invoices
        - Add foreign key constraints
        - Add composite indexes for performance
        - Update unique constraints to include tenant_id

      2. Security
        - Ensure data isolation between tenants
        - Maintain referential integrity
    */

    public function up(): void
    {
        // Users
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'email']);
        });

        // Clients
        Schema::table('clients', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'company_name']);
        });

        // Categories - permettre aux tenants d'avoir leurs propres catégories
        Schema::table('categories', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Modifier la contrainte unique pour inclure tenant_id
            $table->dropUnique(['slug']);
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'name']);
        });

        // Products
        Schema::table('products', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Modifier la contrainte unique pour le SKU
            $table->dropUnique(['sku']);
            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'is_active']);
        });

        // Quotes
        Schema::table('quotes', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Modifier la contrainte unique pour quote_number
            $table->dropUnique(['quote_number']);
            $table->unique(['tenant_id', 'quote_number']);
            $table->index(['tenant_id', 'status']);
        });

        // Orders
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Modifier la contrainte unique pour order_number
            $table->dropUnique(['order_number']);
            $table->unique(['tenant_id', 'order_number']);
            $table->index(['tenant_id', 'status']);
        });

        // Invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Modifier la contrainte unique pour number
            $table->dropUnique(['number']);
            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'status']);
        });

        // Brands
        Schema::table('brands', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            $table->dropUnique(['slug']);
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'name']);
        });

        // Providers
        Schema::table('providers', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'is_active']);
        });

        // Stock movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'movement_date']);
        });
    }

    public function down(): void
    {
        $tables = [
            'stock_movements', 'providers', 'brands', 'invoices', 
            'orders', 'quotes', 'products', 'categories', 'clients', 'users'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['tenant_id']);
                $blueprint->dropColumn('tenant_id');
            });
        }
    }
};