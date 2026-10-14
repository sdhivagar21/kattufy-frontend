<?php

namespace App\Modules\Providers\Models;

use App\Modules\Geography\Models\Locality;
use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PortfolioItem extends Model
{
    use HasUserAttribution, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['completed_on' => 'date', 'is_published' => 'boolean'];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function image(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where('collection', Media::COLLECTION_PORTFOLIO)->oldestOfMany();
    }
}
