<?php

namespace App\Modules\Geography\Services;

use App\Modules\Geography\Models\Locality;
use App\Modules\Geography\Models\Pincode;
use Illuminate\Support\Str;

/** Turns what a person types (a locality name, alias or PIN code) into a locality. */
class LocationResolver
{
    public function fromPincode(?string $code): ?Locality
    {
        if (! $code || ! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        return Pincode::where('code', $code)->first()?->localities()->first();
    }

    public function resolve(?string $input): ?Locality
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        if (preg_match('/^\d{6}$/', $input)) {
            return $this->fromPincode($input);
        }

        $needle = mb_strtolower($input);

        return Locality::query()
            ->where('slug', Str::slug($input))
            ->orWhereRaw('LOWER(name_en) = ?', [$needle])
            ->orWhere('name_ta', $input)
            ->orWhereRaw('LOWER(aliases) LIKE ?', ['%'.$needle.'%'])
            ->first();
    }
}
