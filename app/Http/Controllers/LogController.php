<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        // Heads / Super Admin (logs.view) see everyone; others see only their own.
        $canViewAll = $user->can('logs.view');

        $query = ActivityLog::with('user')->latest();

        if (! $canViewAll) {
            $query->where('user_id', $user->id);
        } elseif ($actor = $request->input('actor_id')) {
            $query->where('user_id', $actor);
        }

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "%{$action}%");
        }
        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        return view('logs.index', [
            'logs' => $query->paginate(25)->withQueryString(),
            'users' => $canViewAll ? User::orderBy('name')->get() : collect(),
            'canViewAll' => $canViewAll,
            'actions' => [
                'login' => 'Logins', 'logout' => 'Logouts', 'login.failed' => 'Failed logins',
                'documents' => 'Document actions', 'users' => 'User changes',
                'settings' => 'Settings changes', 'roles' => 'Role changes', 'divisions' => 'Division changes',
            ],
        ]);
    }
}
