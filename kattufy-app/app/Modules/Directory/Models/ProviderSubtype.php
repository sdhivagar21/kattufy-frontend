<?php

namespace App\Modules\Directory\Models;

use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderSubtype extends Model
{
    use HasUserAttribution;

    protected $guarded = ['id'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(ProviderType::class, 'provider_type_id');
    }
}
