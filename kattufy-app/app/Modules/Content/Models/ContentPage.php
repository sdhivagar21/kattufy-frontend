<?php

namespace App\Modules\Content\Models;

use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Guides, legal pages and other content. Bodies are Markdown; raw HTML is
 * escaped when rendered, so no stored markup reaches the page.
 */
class ContentPage extends Model
{
    use HasUserAttribution, LogsActivity, SoftDeletes;

    public const TYPES = ['guide' => 'Guide', 'legal' => 'Legal page', 'page' => 'Page'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'ta_reviewed_at' => 'datetime'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /** Tamil body only once content staff have marked it reviewed (Architecture §10). */
    public function bodyFor(string $locale): array
    {
        if ($locale === 'ta' && $this->ta_status === 'reviewed' && filled($this->body_ta)) {
            return [$this->body_ta, 'ta'];
        }

        return [$this->body_en, 'en'];
    }

    public function titleFor(string $locale): array
    {
        if ($locale === 'ta' && $this->ta_status === 'reviewed' && filled($this->title_ta)) {
            return [$this->title_ta, 'ta'];
        }

        return [$this->title_en, 'en'];
    }

    public static function renderMarkdown(?string $markdown): string
    {
        return Str::markdown((string) $markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('content')
            ->logOnly(['title_en', 'title_ta', 'status', 'ta_status', 'slug'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
