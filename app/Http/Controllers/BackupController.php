<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function __construct(private BackupService $backups)
    {
    }

    public function index()
    {
        return view('backups.index', [
            'backups' => $this->backups->list(),
            'usage' => $this->backups->diskUsage(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $result = $this->backups->create();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Backup failed: '.$e->getMessage());
        }

        ActivityLog::record(
            'backups.store',
            'Created a backup: '.$result['filename'].' ('.BackupService::formatBytes($result['size']).')'
        );

        return back()->with('success', 'Backup created: '.$result['filename']);
    }

    public function download(string $filename)
    {
        $path = $this->backups->path($filename);
        abort_unless($path, 404);

        ActivityLog::record('backups.download', 'Downloaded a backup: '.basename($path));

        return response()->download($path);
    }

    public function destroy(string $filename)
    {
        $deleted = $this->backups->delete($filename);
        abort_unless($deleted, 404);

        ActivityLog::record('backups.destroy', 'Deleted a backup: '.basename($filename));

        return back()->with('success', 'Backup deleted.');
    }
}
