<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user()) {
            return redirect('login');
        }

        // Mendukung multiple roles dipisah dengan | misal: 'admin|kasir'
        $roles = explode('|', $role);

        if (! in_array($request->user()->role->name, $roles)) {
            abort(403, "Akses ditolak. Halaman ini butuh hak akses: " . str_replace('|', ' atau ', $role));
        }

        return $next($request);
    }
}
