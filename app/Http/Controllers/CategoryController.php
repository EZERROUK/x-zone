<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Models\AttributeOption;
use App\Http\Requests\CategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Category::query()
            ->with(['parent', 'children'])
            ->withCount(['products']);

        // Filtres
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('parent_id')) {
            if ($request->parent_id === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $categories = $query->withTrashed()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($request->input('per_page', 15))
            ->appends($request->all());

        // Ajouter les URLs des images
        $categories->getCollection()->transform(function ($category) {
            $category->image_url = $category->getImageUrl();
            return $category;
        });

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'parent_id', 'status']),
            'parentCategories' => Category::whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Categories/Create', [
            'parentCategories' => Category::whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = Category::create($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie créée avec succès.');
    }

    public function show(Category $category): Response
    {
        // Charger les relations avec pagination pour les produits
        $category->load(['parent', 'children', 'attributes.options']);
        
        $products = $category->products()
            ->with(['brand', 'currency'])
            ->where('is_active', true)
            ->paginate(12);

        return Inertia::render('Categories/Show', [
            'category' => array_merge($category->toArray(), [
                'image_url' => $category->getImageUrl(),
                'full_name' => $category->getFullName(),
                'depth' => $category->getDepth(),
            ]),
            'products' => $products,
        ]);
    }

    public function edit(Category $category): Response
    {
        $category->load(['attributes.options']);
        
        $parentCategories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Categories/Edit', [
            'category' => $category,
            'parentCategories' => $parentCategories,
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();
        
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie mise à jour avec succès.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Vérifier s'il y a des produits associés
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer une catégorie contenant des produits.');
        }

        // Vérifier s'il y a des sous-catégories
        if ($category->children()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer une catégorie ayant des sous-catégories.');
        }

        $category->delete();
        return back()->with('success', 'Catégorie supprimée.');
    }

    public function restore($id): RedirectResponse
    {
        $category = Category::withTrashed()->findOrFail($id);
        $category->restore();
        return back()->with('success', 'Catégorie restaurée.');
    }

    public function forceDelete($id): RedirectResponse
    {
        $category = Category::withTrashed()->findOrFail($id);
        
        // Vérification finale avant suppression définitive
        if ($category->products()->withTrashed()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer définitivement une catégorie ayant des produits.');
        }

        $category->forceDelete();
        return back()->with('success', 'Catégorie supprimée définitivement.');
    }

    // Gestion des attributs de catégorie
    public function attributes(Category $category): Response
    {
        $attributes = $category->attributes()
            ->with('options')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Categories/Attributes', [
            'category' => $category,
            'attributes' => $attributes,
        ]);
    }

    public function storeAttribute(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:category_attributes,slug,NULL,id,category_id,' . $category->id,
            'type' => 'required|in:text,textarea,number,decimal,boolean,select,multiselect,date,url,email',
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:20',
            'default_value' => 'nullable|string',
            'validation_rules' => 'nullable|array',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'is_searchable' => 'boolean',
            'show_in_listing' => 'boolean',
            'sort_order' => 'nullable|integer',
            'options' => 'nullable|array', // Pour select/multiselect
            'options.*.label' => 'required|string',
            'options.*.value' => 'required|string',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $attribute = $category->attributes()->create($validated);

        // Créer les options si c'est un select/multiselect
        if (in_array($validated['type'], ['select', 'multiselect']) && !empty($validated['options'])) {
            foreach ($validated['options'] as $index => $option) {
                $attribute->options()->create([
                    'label' => $option['label'],
                    'value' => $option['value'],
                    'sort_order' => $index,
                ]);
            }
        }

        return back()->with('success', 'Attribut créé avec succès.');
    }

    public function updateAttribute(Request $request, Category $category, CategoryAttribute $attribute): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:category_attributes,slug,' . $attribute->id . ',id,category_id,' . $category->id,
            'type' => 'required|in:text,textarea,number,decimal,boolean,select,multiselect,date,url,email',
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:20',
            'default_value' => 'nullable|string',
            'validation_rules' => 'nullable|array',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'is_searchable' => 'boolean',
            'show_in_listing' => 'boolean',
            'sort_order' => 'nullable|integer',
            'options' => 'nullable|array',
            'options.*.label' => 'required|string',
            'options.*.value' => 'required|string',
        ]);

        $attribute->update($validated);

        // Mettre à jour les options
        if (in_array($validated['type'], ['select', 'multiselect'])) {
            $attribute->options()->delete();
            
            if (!empty($validated['options'])) {
                foreach ($validated['options'] as $index => $option) {
                    $attribute->options()->create([
                        'label' => $option['label'],
                        'value' => $option['value'],
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        return back()->with('success', 'Attribut mis à jour avec succès.');
    }

    public function destroyAttribute(Category $category, CategoryAttribute $attribute): RedirectResponse
    {
        // Vérifier s'il y a des valeurs associées
        if ($attribute->productValues()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer un attribut ayant des valeurs associées.');
        }

        $attribute->delete();
        return back()->with('success', 'Attribut supprimé.');
    }
}