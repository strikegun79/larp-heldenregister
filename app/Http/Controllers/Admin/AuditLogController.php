<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Audit-Log-Ansicht für Admins (ADM-08).
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::orderByDesc('created_at');

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($actor = $request->input('actor')) {
            $query->where('actor_name', 'like', '%'.$actor.'%');
        }

        $logs = $query->paginate(50)->withQueryString();

        $actions = AuditLog::distinct()->orderBy('action')->pluck('action');

        return view('admin.audit_logs.index', compact('logs', 'actions'));
    }
}
