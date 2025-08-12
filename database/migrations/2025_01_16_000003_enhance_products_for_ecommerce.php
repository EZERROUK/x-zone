<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
      # Enhance products table for e-commerce

      1. Changes
        - Add SEO fields (meta_title, meta_description, slug)
        - Add e-commerce specific fields (weight, dimensions, type)
        - Add inventory management fields
        - Add pricing fields (compare_at_price, cost_price)
        - Add visibility and featured flags

      2. New Features
        - Support for virtual/downloadable products
        - Better inventory tracking
        - SEO optimization
        - Product visibility control
    */

    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // SEO
            $table->string('slug')->nullable()->after('name');
            $table->string('meta_title')->nullable()->after('description');
            $table->text('meta_description')->nullable()->after('meta_title');
            
            // E-commerce
            $table->enum('type', ['physical', 'digital', 'service'])->default('physical')->after('description');
            $table->decimal('compare_at_price', 12, 2)->nullable()->after('price'); // Prix barré
            $table->decimal('cost_price', 12, 2)->nullable()->after('compare_at_price'); // Prix de revient
            
            // Dimensions et poids (pour livraison)
            $table->decimal('weight', 8, 2)->nullable()->after('stock_quantity'); // kg
            $table->decimal('length', 8, 2)->nullable()->after('weight'); // cm
            $table->decimal('width', 8, 2)->nullable()->after('length'); // cm
            $table->decimal('height', 8, 2)->nullable()->after('width'); // cm
            
            // Gestion stock avancée
            $table->boolean('track_inventory')->default(true)->after('stock_quantity');
            $table->integer('low_stock_threshold')->default(5)->after('track_inventory');
            $table->boolean('allow_backorder')->default(false)->after('low_stock_threshold');
            
            // Visibilité
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->enum('visibility', ['public', 'private', 'hidden'])->default('public')->after('is_featured');
            $table->timestamp('available_from')->nullable()->after('visibility');
            $table->timestamp('available_until')->nullable()->after('available_from');
            
            // Produits numériques
            $table->string('download_url')->nullable()->after('image_main');
            $table->integer('download_limit')->nullable()->after('download_url');
            $table->integer('download_expiry_days')->nullable()->after('download_limit');
            
            // Index pour performance
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'visibility', 'is_active']);
            $table->index(['tenant_id', 'is_featured']);
        });

        // Ajouter slug unique par tenant
        Schema::table('products', function (Blueprint $table) {
            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'slug']);
            
            $table->dropColumn([
                'slug', 'meta_title', 'meta_description', 'type',
                'compare_at_price', 'cost_price', 'weight', 'length',
                'width', 'height', 'track_inventory', 'low_stock_threshold',
                'allow_backorder', 'is_featured', 'visibility',
                'available_from', 'available_until', 'download_url',
                'download_limit', 'download_expiry_days'
            ]);
        });
    }
};