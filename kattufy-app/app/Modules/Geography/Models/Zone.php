<?php

namespace App\Modules\Geography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A service zone; providers declare the zones they serve. */
class Zone extends Model
{
    protected $guarded = ['id'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class)->orderBy('sort_order');
    }
}
