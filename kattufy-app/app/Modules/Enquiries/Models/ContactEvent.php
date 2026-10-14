<?php

namespace App\Modules\Enquiries\Models;

use App\Models\User;
use App\Modules\Providers\Models\Provider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Every contact action: who revealed which provider, how, when. */
class ContactEvent extends Model
{
    protected $guarded = ['id'];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
