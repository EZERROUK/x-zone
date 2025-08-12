<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
      # Create e-commerce specific tables

      1. New Tables
        - `product_variants` (variations de produits : taille, couleur, etc.)
        - `product_categories` (relation many-to-many pour catégories multiples)
        - `carts` (paniers clients)
        - `cart_items` (articles dans les paniers)
        - `customer_addresses` (adresses de livraison)
        - `shipping_methods` (méthodes de livraison)
        - `payment_methods` (méthodes de paiement)

      2. Security
        - Enable RLS-like behavior via tenant scoping
        - Add proper indexes for performance
    */

    public function up(): void
    {
        // Product Variants (pour tailles, couleurs, etc.)
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id');
            $table->string('name'); // ex: "Rouge - XL"
            $table->string('sku')->nullable();
            $table->decimal('price_modifier', 8, 2)->default(0); // +/- par rapport au prix de base
            $table->integer('stock_quantity')->default(0);
            $table->json('attributes'); // {"color": "rouge", "size": "XL"}
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'product_id']);
        });

        // Relation many-to-many pour catégories multiples
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('product_id');
            $table->foreignId('category_id');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->unique(['product_id', 'category_id']);
        });

        // Paniers
        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('session_id')->nullable(); // Pour invités
            $table->foreignId('user_id')->nullable(); // Pour utilisateurs connectés
            $table->json('metadata')->nullable(); // Données additionnelles
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['tenant_id', 'session_id']);
            $table->index(['tenant_id', 'user_id']);
        });

        // Articles dans les paniers
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('cart_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Prix au moment de l'ajout
            $table->json('product_snapshot'); // Snapshot du produit
            $table->timestamps();

            $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            $table->unique(['cart_id', 'product_id', 'variant_id']);
        });

        // Adresses clients
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->foreignId('client_id');
            $table->string('type')->default('shipping'); // shipping, billing
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code');
            $table->string('country');
            $table->string('phone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->index(['tenant_id', 'client_id']);
        });

        // Méthodes de livraison
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('base_cost', 8, 2)->default(0);
            $table->decimal('cost_per_kg', 8, 2)->default(0);
            $table->decimal('free_shipping_threshold', 8, 2)->nullable();
            $table->integer('estimated_days_min')->default(1);
            $table->integer('estimated_days_max')->default(7);
            $table->json('available_countries')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'is_active']);
        });

        // Méthodes de paiement
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('type'); // stripe, paypal, bank_transfer, cash_on_delivery
            $table->json('configuration')->nullable(); // Clés API, etc.
            $table->decimal('fee_percentage', 5, 2)->default(0);
            $table->decimal('fee_fixed', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('product_variants');
    }
};