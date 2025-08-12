<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if (!$user || !$user->tenant_id) {
            return redirect()->route('tenant.select')
                ->with('error', 'Vous devez être associé à une organisation.');
        }

        $tenant = $user->tenant;
        
        if (!$tenant || !$tenant->isActive()) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Votre organisation est inactive ou expirée.');
        }

        // Partager le tenant dans toute l'application
        app()->instance('current_tenant', $tenant);
        config(['app.current_tenant' => $tenant]);

        return $next($request);
    }
}