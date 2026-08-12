<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');

        if ($project instanceof Project) {
            abort_unless(
                Filament::auth()->user()?->canAccessTenant($project) ?? false,
                403,
            );
        }

        return $next($request);
    }
}
