<?php

namespace App\Modules\Directory\Models;

use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Build-stage groupings, e.g. the /building-a-house/ hub. */
class CategoryGroup extends Model
{
    use HasUserAttribution;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->where('is_active', true)->orderBy('sort_order');
    }
}
