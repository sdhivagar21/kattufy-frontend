<?php

namespace App\Modules\Geography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** A PIN code resolves to a locality; it is never a parent. */
class Pincode extends Model
{
    protected $guarded = ['id'];

    public function localities(): BelongsToMany
    {
        return $this->belongsToMany(Locality::class, 'locality_pincode')->withTimestamps();
    }
}
