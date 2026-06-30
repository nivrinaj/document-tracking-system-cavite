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
        // Documents
        'documents.store' => 'Encoded a new document',
        'documents.update' => 'Updated document details',
        'documents.destroy' => 'Deleted a document',
        'documents.assign' => 'Assigned a document',
        'documents.release' => 'Released a document',
        'documents.receive' => 'Received a document',
        'documents.forward' => 'Forwarded a document',
        'documents.transfer' => 'Transferred a document to another office',
        'documents.archive' => 'Archived a document',
        'documents.acknowledge' => 'Acknowledged a document',
        'documents.distribute' => 'Distributed a document',
        'documents.pending' => 'Marked a document as pending',
        'documents.reject' => 'Rejected a document',
        'documents.reopen' => 'Reopened a document',
        'documents.resume' => 'Resumed a document from pending',
        'documents.link' => 'Linked two documents',
        'documents.unlink' => 'Unlinked two documents',
        'documents.batchReceive.store' => 'Batch received multiple documents',
        'documents.items.decision' => 'Made a route item decision',
        // Attachments
        'attachments.store' => 'Added an attachment',
        'attachments.digitalCopy' => 'Added a digital copy',
        'attachments.destroy' => 'Deleted an attachment',
        // Users
        'users.store' => 'Created a user account',
        'users.update' => 'Updated a user account',
        'users.destroy' => 'Deleted a user account',
        // Departments & divisions
        'departments.store' => 'Created a department',
        'departments.update' => 'Updated a department',
        'departments.destroy' => 'Deleted a department',
        'divisions.store' => 'Created a division',
        'divisions.update' => 'Updated a division',
        'divisions.destroy' => 'Deleted a division',
        // Document types
        'document-types.store' => 'Added a document type',
        'document-types.update' => 'Updated a document type',
        'document-types.destroy' => 'Deleted a document type',
        // Accounting setup
        'accounting.funds.store' => 'Added a fund',
        'accounting.funds.destroy' => 'Deleted a fund',
        'accounting.centers.store' => 'Added a responsibility center',
        'accounting.centers.destroy' => 'Deleted a responsibility center',
        'accounting.centers.projects.update' => 'Updated a project',
        'accounting.centers.projects.destroy' => 'Deleted a project',
        'accounting.natures.store' => 'Added a nature of transaction',
        'accounting.natures.destroy' => 'Deleted a nature of transaction',
        // Roles
        'roles.store' => 'Created a role',
        'roles.update' => 'Updated a role',
        'roles.destroy' => 'Deleted a role',
        // Settings
        'settings.update' => 'Updated system settings',
        'settings.resetData' => 'Reset system data (Danger Zone)',
        'reports.settings.save' => 'Updated report settings',
        // Calendar
        'work-calendar.holidays.store' => 'Added a holiday',
        'work-calendar.holidays.destroy' => 'Deleted a holiday',
        'work-calendar.team.store' => 'Added a team calendar entry',
        'work-calendar.team.destroy' => 'Removed a team calendar entry',
        // Documentation
        'documentation.store' => 'Created a help page',
        'documentation.update' => 'Updated a help page',
        'documentation.destroy' => 'Deleted a help page',
        // Profile
        'profile.update' => 'Updated their profile',
        'profile.destroy' => 'Deleted their account',
        // Messages
        'messages.start' => 'Started a conversation',
        'messages.store' => 'Sent a message',
        'messages.group' => 'Created a group conversation',
    ];

    /** Routes handled inline by their controllers (with detailed diffs) or via auth events. */
    private array $ignore = [
        'login', 'logout', 'register',
        'password.email', 'password.store', 'password.update', 'password.confirm',
        'notifications.read', 'notifications.readAll',
        'settings.update', 'reports.settings.save', 'qr-slip.settings.save',
        'users.store', 'users.update',
        'work-calendar.holidays.store', 'work-calendar.holidays.destroy',
        'work-calendar.team.store', 'work-calendar.team.destroy',
        'documents.store',
        'accounting.funds.store', 'accounting.centers.store',
        'accounting.centers.projects.store',
        'accounting.natures.store',
        'departments.store', 'divisions.store', 'document-types.store', 'roles.store', 'documentation.store',
    ];

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
            return;
        }

        $route = $request->route();
        $name = $route?->getName();

        if (! $name || str_starts_with($name, 'generated::') || in_array($name, $this->ignore)) {
            return;
        }

        $description = $this->map[$name] ?? ucfirst(str_replace(['.', '_'], ' ', $name));

        $subject = null;
        foreach ($route->parameters() as $param) {
            if ($param instanceof Model) {
                $subject = $param;
                break;
            }
        }
        if ($subject) {
            $label = $subject->tracking_code
                ?? $subject->name
                ?? $subject->title
                ?? $subject->label
                ?? null;
            $id = $subject->getKey();
            $description .= ': ' . ($label ? "{$label} (#{$id})" : "#{$id}");
        }

        ActivityLog::record($name, $description, $subject);
    }
}
