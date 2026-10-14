<?php

namespace App\Modules\Directory\Models;

use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A specialisation within a category, e.g. "Approval drawings". */
class Service extends Model
{
    use HasUserAttribution;

    protected $guarded = ['id'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
