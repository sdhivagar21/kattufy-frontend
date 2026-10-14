<?php

namespace App\Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

/** One row per search (no personal data), for the no-result KPI. */
class SearchLog extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['used_fallback' => 'boolean'];
    }
}
