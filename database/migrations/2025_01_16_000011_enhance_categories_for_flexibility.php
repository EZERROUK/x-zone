<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
      # Enhance categories for complete flexibility

      1. Changes
        - Add hierarchical support (parent/child)
        - Add SEO fields
        - Add image and description
        - Add visibility controls
        - Add sort order for custom ordering

      2. Features
        - Categories can have unlimited subcategories
        - Each category can define its own custom attributes
        - SEO optimization per category
        - Image support for category display
    */

    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Hiérarchie
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('categories')->onDelete('cascade');
            
            // Contenu
            $table->text('description')->nullable()->after('slug');
            $table->string('image_path')->nullable()->after('description');
            
            // SEO
            $table->string('meta_title')->nullable()->after('image_path');
            $table->text('meta_description')->nullable()->after('meta_title');
            
            // Gestion
            $table->boolean('is_active')->default(true)->after('meta_description');
            $table->integer('sort_order')->default(0)->after('is_active');
            
            // Index pour performance
            $table->index(['parent_id', 'is_active']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'parent_id', 'description', 'image_path', 
                'meta_title', 'meta_description', 'is_active', 'sort_order'
            ]);
        });
    }
};