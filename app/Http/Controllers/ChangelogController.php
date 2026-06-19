<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;

class ChangelogController extends Controller
{
    public function index()
    {
        $path = base_path('CHANGELOG.md');
        $markdown = is_file($path) ? file_get_contents($path) : '# Changelog\n\n_No changelog file found._';

        return view('changelog.index', [
            'html' => Str::markdown($markdown),
            'version' => config('version.number'),
            'released' => config('version.released'),
        ]);
    }
}
