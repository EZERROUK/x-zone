<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function select(): Response
    {
        return Inertia::render('tenant/select');
    }

    public function create(): Response
    {
        return Inertia::render('tenant/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:tenants,slug|regex:/^[a-z0-9-]+$/',
            'plan' => 'required|in:free,starter,professional,enterprise',
        ]);

        $limits = $this->getPlanLimits($validated['plan']);

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'plan' => $validated['plan'],
            'status' => 'active',
            'max_users' => $limits['max_users'],
            'max_products' => $limits['max_products'],
            'max_orders_per_month' => $limits['max_orders_per_month'],
            'monthly_price' => $limits['monthly_price'],
            'expires_at' => $validated['plan'] === 'free' ? null : now()->addMonth(),
        ]);

        // Associer l'utilisateur au tenant
        auth()->user()->update(['tenant_id' => $tenant->id]);

        // Créer les catégories par défaut
        $this->createDefaultCategories($tenant);

        return redirect()->route('dashboard')
            ->with('success', 'Organisation créée avec succès !');
    }

    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $user = auth()->user();
        
        // Vérifier que l'utilisateur a accès à ce tenant
        if (!$user->hasRole('SuperAdmin') && $user->tenant_id !== $validated['tenant_id']) {
            return back()->with('error', 'Accès non autorisé à cette organisation.');
        }

        $user->update(['tenant_id' => $validated['tenant_id']]);

        return redirect()->route('dashboard')
            ->with('success', 'Organisation changée avec succès.');
    }

    private function getPlanLimits(string $plan): array
    {
        return match ($plan) {
            'free' => [
                'max_users' => 1,
                'max_products' => 50,
                'max_orders_per_month' => 20,
                'monthly_price' => 0,
            ],
            'starter' => [
                'max_users' => 3,
                'max_products' => 500,
                'max_orders_per_month' => 100,
                'monthly_price' => 29.99,
            ],
            'professional' => [
                'max_users' => 10,
                'max_products' => 2000,
                'max_orders_per_month' => 500,
                'monthly_price' => 79.99,
            ],
            'enterprise' => [
                'max_users' => 50,
                'max_products' => 10000,
                'max_orders_per_month' => 2000,
                'monthly_price' => 199.99,
            ],
        };
    }

    private function createDefaultCategories(Tenant $tenant): void
    {
        $defaultCategories = [
            ['name' => 'Électronique', 'slug' => 'electronique'],
            ['name' => 'Informatique', 'slug' => 'informatique'],
            ['name' => 'Accessoires', 'slug' => 'accessoires'],
        ];

        foreach ($defaultCategories as $index => $categoryData) {
            \App\Models\Category::create([
                'tenant_id' => $tenant->id,
                'name' => $categoryData['name'],
                'slug' => $categoryData['slug'],
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }
}