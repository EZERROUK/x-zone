<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Category, CategoryAttribute, AttributeOption};
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Catégories racines avec leurs attributs spécifiques
        $rootCategories = [
            [
                'name' => 'Informatique',
                'slug' => 'informatique',
                'description' => 'Matériel et composants informatiques',
                'children' => [
                    [
                        'name' => 'Processeurs',
                        'slug' => 'processeurs',
                        'attributes' => [
                            ['name' => 'Marque', 'type' => 'select', 'options' => ['Intel', 'AMD']],
                            ['name' => 'Fréquence', 'type' => 'decimal', 'unit' => 'GHz'],
                            ['name' => 'Nombre de cœurs', 'type' => 'number'],
                            ['name' => 'Socket', 'type' => 'select', 'options' => ['LGA1700', 'AM4', 'AM5']],
                        ]
                    ],
                    [
                        'name' => 'Mémoire RAM',
                        'slug' => 'memoire-ram',
                        'attributes' => [
                            ['name' => 'Capacité', 'type' => 'select', 'unit' => 'GB', 'options' => ['4', '8', '16', '32', '64']],
                            ['name' => 'Type', 'type' => 'select', 'options' => ['DDR4', 'DDR5']],
                            ['name' => 'Fréquence', 'type' => 'number', 'unit' => 'MHz'],
                        ]
                    ],
                    [
                        'name' => 'Cartes graphiques',
                        'slug' => 'cartes-graphiques',
                        'attributes' => [
                            ['name' => 'Marque', 'type' => 'select', 'options' => ['NVIDIA', 'AMD']],
                            ['name' => 'Mémoire VRAM', 'type' => 'select', 'unit' => 'GB', 'options' => ['4', '6', '8', '12', '16', '24']],
                            ['name' => 'Interface', 'type' => 'select', 'options' => ['PCIe 4.0', 'PCIe 3.0']],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Électronique',
                'slug' => 'electronique',
                'description' => 'Appareils électroniques grand public',
                'children' => [
                    [
                        'name' => 'Smartphones',
                        'slug' => 'smartphones',
                        'attributes' => [
                            ['name' => 'Marque', 'type' => 'select', 'options' => ['Apple', 'Samsung', 'Google', 'OnePlus']],
                            ['name' => 'Taille écran', 'type' => 'decimal', 'unit' => 'pouces'],
                            ['name' => 'Stockage', 'type' => 'select', 'unit' => 'GB', 'options' => ['64', '128', '256', '512', '1024']],
                            ['name' => 'Couleur', 'type' => 'select', 'options' => ['Noir', 'Blanc', 'Bleu', 'Rouge', 'Vert']],
                            ['name' => '5G', 'type' => 'boolean'],
                        ]
                    ],
                    [
                        'name' => 'Ordinateurs portables',
                        'slug' => 'ordinateurs-portables',
                        'attributes' => [
                            ['name' => 'Processeur', 'type' => 'text'],
                            ['name' => 'RAM', 'type' => 'select', 'unit' => 'GB', 'options' => ['8', '16', '32', '64']],
                            ['name' => 'Stockage', 'type' => 'select', 'unit' => 'GB', 'options' => ['256', '512', '1024', '2048']],
                            ['name' => 'Taille écran', 'type' => 'select', 'unit' => 'pouces', 'options' => ['13', '14', '15', '16', '17']],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Mode & Accessoires',
                'slug' => 'mode-accessoires',
                'description' => 'Vêtements et accessoires',
                'children' => [
                    [
                        'name' => 'Vêtements',
                        'slug' => 'vetements',
                        'attributes' => [
                            ['name' => 'Taille', 'type' => 'select', 'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL']],
                            ['name' => 'Couleur', 'type' => 'multiselect', 'options' => ['Noir', 'Blanc', 'Rouge', 'Bleu', 'Vert', 'Jaune']],
                            ['name' => 'Matière', 'type' => 'select', 'options' => ['Coton', 'Polyester', 'Laine', 'Soie']],
                            ['name' => 'Lavable en machine', 'type' => 'boolean'],
                        ]
                    ],
                ]
            ],
        ];

        foreach ($rootCategories as $rootData) {
            $root = Category::create([
                'name' => $rootData['name'],
                'slug' => $rootData['slug'],
                'description' => $rootData['description'] ?? null,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            foreach ($rootData['children'] ?? [] as $childData) {
                $child = Category::create([
                    'name' => $childData['name'],
                    'slug' => $childData['slug'],
                    'parent_id' => $root->id,
                    'is_active' => true,
                    'sort_order' => 0,
                ]);

                // Créer les attributs pour cette catégorie
                foreach ($childData['attributes'] ?? [] as $index => $attrData) {
                    $attribute = CategoryAttribute::create([
                        'category_id' => $child->id,
                        'name' => $attrData['name'],
                        'slug' => Str::slug($attrData['name']),
                        'type' => $attrData['type'],
                        'unit' => $attrData['unit'] ?? null,
                        'is_required' => $attrData['required'] ?? false,
                        'is_filterable' => true,
                        'is_searchable' => in_array($attrData['type'], ['text', 'select']),
                        'show_in_listing' => $index < 2, // Afficher les 2 premiers en listing
                        'sort_order' => $index,
                        'is_active' => true,
                    ]);

                    // Créer les options pour select/multiselect
                    if (in_array($attrData['type'], ['select', 'multiselect']) && !empty($attrData['options'])) {
                        foreach ($attrData['options'] as $optIndex => $optionLabel) {
                            AttributeOption::create([
                                'attribute_id' => $attribute->id,
                                'label' => $optionLabel,
                                'value' => Str::slug($optionLabel),
                                'sort_order' => $optIndex,
                                'is_active' => true,
                            ]);
                        }
                    }
                }
            }
        }
    }
}