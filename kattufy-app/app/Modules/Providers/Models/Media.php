<?php

namespace App\Modules\Providers\Models;

use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

/**
 * Every uploaded file. Portfolio photos are public; credential documents stay
 * on the private disk and are only ever served through a short-lived signed
 * link to someone allowed to see them (DocumentController).
 */
class Media extends Model
{
    use HasUserAttribution, SoftDeletes;

    public const COLLECTION_DOCUMENT = 'credential_document';

    public const COLLECTION_PORTFOLIO = 'portfolio';

    protected $table = 'media';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['variants' => 'array', 'processed_at' => 'datetime', 'size_bytes' => 'integer'];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    /** Public image URL (portfolio). Never used for private documents. */
    public function publicUrl(int $width = 800): ?string
    {
        if ($this->isPrivate()) {
            return null;
        }

        $variants = $this->variants ?? [];

        return asset($variants[(string) $width] ?? $variants['800'] ?? $this->original_path);
    }

    /** Short-lived signed link for a private document. */
    public function temporaryUrl(): string
    {
        return URL::temporarySignedRoute('documents.show', now()->addMinutes(config('kattufy.uploads.document_link_minutes')), ['media' => $this->getKey()]);
    }

    public function humanSize(): string
    {
        $kb = $this->size_bytes / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1).' MB' : number_format(max(1, $kb)).' KB';
    }
}
