<?php

namespace App\Http\Controllers;

use App\Models\DocumentLog;
use App\Models\User;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentLog::with(['document', 'actor', 'toUser', 'fromUser'])->latest();

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }
        if ($actor = $request->input('actor_id')) {
            $query->where('actor_id', $actor);
        }
        if ($search = $request->input('search')) {
            $query->whereHas('document', fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('tracking_code', 'like', "%{$search}%"));
        }

        return view('logs.index', [
            'logs' => $query->paginate(20)->withQueryString(),
            'users' => User::orderBy('name')->get(),
            'actions' => ['encoded', 'assigned', 'released', 'received', 'forwarded', 'archived', 'completed'],
        ]);
    }
}
