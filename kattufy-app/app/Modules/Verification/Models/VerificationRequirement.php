<?php

namespace App\Modules\Verification\Models;

use App\Modules\Directory\Models\Category;
use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Seeded lookup: category → credential type. Defines what PublishGate requires. */
class VerificationRequirement extends Model
{
    use HasUserAttribution;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function credentialType(): BelongsTo
    {
        return $this->belongsTo(CredentialType::class);
    }
}
