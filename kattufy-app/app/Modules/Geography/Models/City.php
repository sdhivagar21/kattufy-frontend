<?php

namespace App\Modules\Geography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Its slug is the city segment of URLs (/architects/chennai/). */
class City extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class)->orderBy('sort_order');
    }

    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class)->orderBy('sort_order');
    }

    public static function launch(): ?self
    {
        return once(fn () => static::where('slug', config('kattufy.launch_city'))->first());
    }
}
