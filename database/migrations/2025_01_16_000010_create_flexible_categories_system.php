<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
      # Create flexible categories and product attributes system

      1. New Tables
        - `category_attributes` - Définit les champs personnalisés par catégorie
        - `product_attribute_values` - Stocke les valeurs des attributs pour chaque produit
        - `attribute_options` - Options prédéfinies pour les attributs (select, radio, etc.)

      2. Changes
        - Supprime la dépendance au fichier config/catalog.php
        - Permet aux utilisateurs de définir leurs propres attributs
        - Support de différents types de champs (text, number, select, boolean, etc.)

      3. Security
        - Validation des types de données
        - Contraintes d'intégrité référentielle
    */

    public function up(): void
    {
        // Attributs personnalisés par catégorie
        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name'); // ex: "Capacité", "Vitesse", "Type de mémoire"
            $table->string('slug'); // ex: "capacity", "speed", "memory_type"
            $table->enum('type', [
                'text', 'textarea', 'number', 'decimal', 'boolean', 
                'select', 'multiselect', 'date', 'url', 'email'
            ]);
            $table->text('description')->nullable();
            $table->json('validation_rules')->nullable(); // ex: {"min": 0, "max": 100, "required": true}
            $table->string('unit')->nullable(); // ex: "GB", "MHz", "W"
            $table->text('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(true); // Peut être utilisé comme filtre
            $table->boolean('is_searchable')->default(true); // Inclus dans la recherche
            $table->boolean('show_in_listing')->default(false); // Affiché dans la liste des produits
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['category_id', 'slug']);
            $table->index(['category_id', 'is_active']);
        });

        // Options prédéfinies pour les attributs de type select/multiselect
        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('category_attributes')->onDelete('cascade');
            $table->string('label'); // ex: "DDR4", "DDR5"
            $table->string('value'); // ex: "ddr4", "ddr5"
            $table->string('color')->nullable(); // Pour affichage coloré
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['attribute_id', 'is_active']);
        });

        // Valeurs des attributs pour chaque produit
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->uuid('product_id');
            $table->foreignId('attribute_id')->constrained('category_attributes')->onDelete('cascade');
            $table->text('value')->nullable(); // Stockage flexible (JSON pour multiselect)
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['product_id', 'attribute_id']);
            $table->index(['attribute_id', 'value']);
        });

        // Table de liaison many-to-many pour catégories multiples
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
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('category_attributes');
    }
};