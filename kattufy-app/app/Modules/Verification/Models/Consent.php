<?php

namespace App\Modules\Verification\Models;

use App\Modules\Providers\Models\Provider;
use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Provider consent: when, how, by whom, and what may be published. */
class Consent extends Model
{
    use HasUserAttribution;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['consented_at' => 'datetime', 'withdrawn_at' => 'datetime', 'scope' => 'array'];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
