<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Gate;

trait RequiresAdminAccess
{
    /**
     * Re-authorize admin access on every request, not just the initial page load.
     */
    public function boot(): void
    {
        Gate::authorize('access-admin');
    }
}
