<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every state-changing action (create/update/delete + workflow steps)
 * into the activity_logs audit trail. Logins/logouts are handled separately
 * via auth events (see AppServiceProvider).
 */
class LogActivity
{
    /** Friendly text for known routes. */
    private array $map = [
        'documents.store' => 'Encoded a document',
        'documents.update' => 'Updated a document',
        'documents.destroy' => 'Deleted a document',
        'documents.assign' => 'Assigned a document',
        'documents.release' => 'Released a document',
        'documents.receive' => 'Received a document',
        'documents.forward' => 'Forwarded a document',
        'documents.archive' => 'Archived a document',
        'users.store' => 'Created a user',
        'users.update' => 'Updated a user',
        'users.destroy' => 'Deleted a user',
        'divisions.store' => 'Created a division',
        'divisions.update' => 'Updated a division',
        'divisions.destroy' => 'Deleted a division',
        'roles.store' => 'Created a role',
        'roles.update' => 'Updated a role',
        'roles.destroy' => 'Deleted a role',
        'settings.update' => 'Updated system settings',
        'documentation.store' => 'Created a documentation page',
        'documentation.update' => 'Updated a documentation page',
        'documentation.destroy' => 'Deleted a documentation page',
        'profile.update' => 'Updated their profile',
        'profile.destroy' => 'Deleted their account',
    ];

    /** Auth flows are logged via events, so skip them here. */
    private array $ignore = ['login', 'logout', 'register', 'password.email', 'password.store', 'password.update', 'password.confirm', 'notifications.read', 'notifications.readAll'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->record($request, $response);
        } catch (\Throwable $e) {
            // never let auditing break a request
        }

        return $response;
    }

    private function record(Request $request, Response $response): void
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return;
        }
        if (! Auth::check()) {
            return;
        }
        if ($response->getStatusCode() >= 400) {
            return; // skip validation failures / errors
        }

        $route = $request->route();
        $name = $route?->getName();

        // Skip unnamed/framework-internal routes (Laravel auto-names them
        // "generated::xxxx", e.g. the POST /login route) and auth flows that
        // are already audited via events.
        if (! $name || str_starts_with($name, 'generated::') || in_array($name, $this->ignore)) {
            return;
        }

        $description = $this->map[$name] ?? ucfirst(str_replace(['.', '_'], ' ', $name));

        // Attach a human label for the affected record, if any.
        $subject = null;
        foreach ($route->parameters() as $param) {
            if ($param instanceof Model) {
                $subject = $param;
                break;
            }
        }
        if ($subject) {
            $label = $subject->tracking_code ?? $subject->name ?? $subject->title ?? ('#'.$subject->getKey());
            $description .= ': '.$label;
        }

        ActivityLog::record($name, $description, $subject);
    }
}
