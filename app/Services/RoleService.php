<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RoleService
{
    public function homeRouteFor(string $role): string
    {
        return (string) config("roles.home_route.{$role}", '/');
    }

    /**
     * A user's primary role = the oldest row in user_roles for them
     * (assigned_at ASC). This standalone build only ever assigns one role
     * per demo user, but the lookup keeps the same shape as the full
     * platform so it's a drop-in if you add more roles later.
     */
    public function primaryRoleFor(int $userId): string
    {
        $slug = DB::table('roles')
            ->join('user_roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_roles.user_id', $userId)
            ->orderBy('user_roles.assigned_at', 'asc')
            ->value('roles.slug');

        return $slug ?? 'data_analyst';
    }
}
