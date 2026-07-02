<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Hand-rolled backup/disk-usage service (no third-party backup package —
 * just mysqldump + ZipArchive, matching the tools already relied on
 * elsewhere in this project). Deliberately create-only: there is no
 * restore() here, by design — restoring a dump is destructive/irreversible
 * and stays a manual DBA action, per the standing decision to never expose
 * a one-click restore in the GUI.
 */
class BackupService
{
    /** Bundle a fresh mysqldump + every attachment into one downloadable zip. */
    public function create(): array
    {
        $disk = $this->disk();
        $timestamp = now()->format('Y-m-d_His');
        $filename = "backup-{$timestamp}.zip";
        $zipPath = storage_path("app/backups/{$filename}");
        $dumpPath = storage_path("app/backups/.tmp-{$timestamp}.sql");

        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $this->dumpDatabase($dumpPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($dumpPath);
            throw new RuntimeException('Could not create the backup archive.');
        }
        $zip->addFile($dumpPath, 'database.sql');
        $this->addDirectoryToZip($zip, config('backup.attachments_path'), 'attachments');
        $zip->close();

        @unlink($dumpPath);

        return [
            'filename' => $filename,
            'size' => $disk->size($filename),
        ];
    }

    /** Existing backups, newest first. */
    public function list(): Collection
    {
        $disk = $this->disk();

        return collect($disk->files())
            ->filter(fn ($f) => str_ends_with($f, '.zip'))
            ->map(fn ($f) => [
                'filename' => $f,
                'size' => $disk->size($f),
                'modified_at' => Carbon::createFromTimestamp($disk->lastModified($f)),
            ])
            ->sortByDesc('modified_at')
            ->values();
    }

    /** Delete one backup by filename — validated against the real file list (no path traversal). */
    public function delete(string $filename): bool
    {
        $disk = $this->disk();
        $filename = basename($filename);
        if (! in_array($filename, $disk->files(), true)) {
            return false;
        }

        return $disk->delete($filename);
    }

    /** Absolute path for a validated backup filename, or null if it doesn't exist. */
    public function path(string $filename): ?string
    {
        $disk = $this->disk();
        $filename = basename($filename);
        if (! in_array($filename, $disk->files(), true)) {
            return null;
        }

        return $disk->path($filename);
    }

    /** DB size, attachments size, backups size, and server disk free/total — for the Storage panel. */
    public function diskUsage(): array
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        $dbSize = (int) (DB::selectOne(
            'SELECT SUM(data_length + index_length) AS size FROM information_schema.tables WHERE table_schema = ?',
            [$database]
        )->size ?? 0);

        $freeBytes = @disk_free_space(storage_path()) ?: 0;
        $totalBytes = @disk_total_space(storage_path()) ?: 0;

        return [
            'db_size' => $dbSize,
            'attachments_size' => $this->directorySize(config('backup.attachments_path')),
            'backups_size' => $this->directorySize(storage_path('app/backups')),
            'free_bytes' => $freeBytes,
            'total_bytes' => $totalBytes,
            'used_percent' => $totalBytes > 0 ? round((($totalBytes - $freeBytes) / $totalBytes) * 100, 1) : null,
        ];
    }

    private function disk()
    {
        return Storage::disk('backups');
    }

    private function dumpDatabase(string $outputPath): void
    {
        $connection = config('database.default');
        $conn = config("database.connections.{$connection}");

        $result = Process::timeout(300)
            ->env(['MYSQL_PWD' => $conn['password'] ?? ''])
            ->run([
                config('backup.mysqldump_path'),
                '--host='.$conn['host'],
                '--port='.$conn['port'],
                '--user='.$conn['username'],
                '--single-transaction',
                $conn['database'],
            ]);

        if (! $result->successful()) {
            throw new RuntimeException('mysqldump failed: '.$result->errorOutput());
        }

        file_put_contents($outputPath, $result->output());
    }

    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $zipSubdir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }
            $relative = $zipSubdir.'/'.substr($file->getPathname(), strlen($dir) + 1);
            $zip->addFile($file->getPathname(), str_replace('\\', '/', $relative));
        }
    }

    private function directorySize(string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }

        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
