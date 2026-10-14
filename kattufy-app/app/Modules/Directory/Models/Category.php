<?php

namespace App\Modules\Directory\Models;

use App\Modules\Geography\Models\City;
use App\Modules\Providers\Models\Provider;
use App\Modules\Verification\Models\VerificationRequirement;
use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** Directory categories; the slug is the first URL segment (/architects/). */
class Category extends Model
{
    use HasUserAttribution, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CategoryGroup::class, 'category_group_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('sort_order');
    }

    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(Provider::class, 'provider_categories')->withPivot('is_primary')->withTimestamps();
    }

    /** Live / Coming soon per city (category_city_status). */
    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'category_city_status')
            ->withPivot(['status', 'is_held', 'hold_reason', 'published_provider_count', 'status_changed_at'])
            ->withTimestamps();
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(VerificationRequirement::class)->where('is_active', true);
    }

    public function isLiveIn(?City $city): bool
    {
        if (! $city) {
            return false;
        }

        $pivot = $this->relationLoaded('cities')
            ? $this->cities->firstWhere('id', $city->id)?->pivot
            : $this->cities()->where('cities.id', $city->id)->first()?->pivot;

        return $pivot?->status === 'live';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('directory')
            ->logOnly(['name_en', 'name_ta', 'slug', 'description_en', 'description_ta', 'is_active', 'sort_order'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
