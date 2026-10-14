<?php

namespace App\Support\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Fills created_by / updated_by from the logged-in user, never from request
 * input. NULL means self sign-up, a scheduled job, a seeder or the system.
 */
trait HasUserAttribution
{
    public static function bootHasUserAttribution(): void
    {
        static::creating(function ($model) {
            if (Auth::check() && $model->created_by === null) {
                $model->created_by = Auth::id();
            }
        });

        static::saving(function ($model) {
            if (Auth::check() && $model->usesUpdatedBy()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    protected function usesUpdatedBy(): bool
    {
        return true;
    }
}
