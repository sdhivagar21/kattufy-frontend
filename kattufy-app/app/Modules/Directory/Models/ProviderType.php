<?php

namespace App\Modules\Directory\Models;

use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kind of provider (professional, contractor, supplier). Required to publish. */
class ProviderType extends Model
{
    use HasUserAttribution;

    protected $guarded = ['id'];

    public function subtypes(): HasMany
    {
        return $this->hasMany(ProviderSubtype::class)->orderBy('sort_order');
    }
}
