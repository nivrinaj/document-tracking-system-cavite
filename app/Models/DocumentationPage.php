<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentationPage extends Model
{
    protected $fillable = [
        'title', 'slug', 'category', 'excerpt', 'content', 'sort_order',
    ];

    protected static function booted(): void
    {
        static::saving(function (DocumentationPage $page) {
            if (empty($page->slug)) {
                $page->slug = static::uniqueSlug($page->title);
            }
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'page';
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /** Render the Markdown body to HTML. */
    public function renderedHtml(): string
    {
        return Str::markdown($this->content ?? '');
    }
}
