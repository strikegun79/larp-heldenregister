<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\View\View;

/**
 * Rollen-Übersicht im Admin (ROLE-04): read-only, zeigt Berechtigungen
 * aus der Rechte-Matrix und Nutzeranzahl je Rolle.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        $matrix = config('permissions.roles', []);

        $roles = Role::withCount('users')
            ->where('id', '>', 0)
            ->orderBy('id')
            ->get()
            ->map(function (Role $role) use ($matrix) {
                $granted = $matrix[$role->slug] ?? [];
                $role->permissions = in_array('*', $granted, true)
                    ? ['* (alle Rechte)']
                    : $granted;

                return $role;
            });

        return view('admin.roles.index', compact('roles'));
    }
}
