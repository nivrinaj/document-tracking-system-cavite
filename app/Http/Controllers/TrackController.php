<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    /**
     * The page a phone lands on after scanning a QR code.
     *
     * The user is already authenticated (auth middleware). We then check the
     * DocumentPolicy: if they are not a concerned party (and not a head), we
     * deliberately show a generic "QR not found" page — they must not learn
     * anything about a document that isn't theirs.
     */
    public function show(Request $request, Document $document)
    {
        $user = $request->user();

        if ($user->cannot('view', $document)) {
            // Wrong user / not the intended recipient.
            return response()->view('track.not-found', [], 404);
        }

        $document->load(['creator', 'currentHolder', 'division', 'logs.actor', 'logs.toUser']);

        return view('track.show', [
            'document' => $document,
            'users' => \App\Models\User::with('division')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
