<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Services\NotificationCatalog;
use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    public function index(Request $request)
    {
        // Order by id, not created_at: several emails in one batch can share the
        // same second-precision timestamp, which would make their order ambiguous.
        $query = EmailLog::query()->orderByDesc('id');

        if ($search = $request->input('search')) {
            $query->where('recipient', 'like', "%{$search}%");
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, [12, 25, 50, 100], true)) {
            $perPage = 25;
        }

        return view('email-logs.index', [
            'logs' => $query->paginate($perPage)->withQueryString(),
            'types' => NotificationCatalog::types(),
            'perPage' => $perPage,
        ]);
    }
}
