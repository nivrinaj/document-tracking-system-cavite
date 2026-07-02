<?php

return [
    // Full path to mysqldump.exe if it isn't on the web server process's PATH
    // (common on IIS app pools). Defaults to assuming it's on PATH.
    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),

    // Directory (relative to storage/app/private) backed up alongside the DB dump.
    'attachments_path' => storage_path('app/private'),
];
