<?php

namespace App\Modules\Enquiries\Models;

use App\Models\User;
use App\Modules\Directory\Models\Category;
use App\Modules\Geography\Models\City;
use App\Modules\Geography\Models\Locality;
use App\Modules\Providers\Models\Provider;
use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** "Can't find what you need?" enquiries, handled by staff. */
class Enquiry extends Model
{
    use HasUserAttribution, LogsActivity;

    public const STATUSES = ['new' => 'New', 'assigned' => 'Assigned', 'matched' => 'Matched', 'closed' => 'Closed'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['preferred_date' => 'date', 'assigned_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function matchedProvider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'matched_provider_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('enquiries')
            ->logOnly(['status', 'assigned_to', 'matched_provider_id', 'outcome'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
