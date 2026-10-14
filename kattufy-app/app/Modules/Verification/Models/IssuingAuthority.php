<?php

namespace App\Modules\Verification\Models;

use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Model;

/** Bodies whose registers credentials are checked against (COA, GCC …). */
class IssuingAuthority extends Model
{
    use HasUserAttribution;

    protected $guarded = ['id'];
}
