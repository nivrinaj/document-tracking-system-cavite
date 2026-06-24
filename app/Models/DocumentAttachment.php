<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAttachment extends Model
{
    protected $fillable = ['document_id', 'kind', 'title', 'file_path', 'file_size', 'uploaded_by'];

    public function hasFile(): bool
    {
        return ! empty($this->file_path);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Human file size, e.g. "1.2 MB". */
    public function humanSize(): string
    {
        $kb = $this->file_size / 1024;

        return $kb >= 1024 ? round($kb / 1024, 1).' MB' : round($kb).' KB';
    }
}
