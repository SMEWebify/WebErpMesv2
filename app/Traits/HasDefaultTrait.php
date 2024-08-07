<?php

namespace App\Traits;

trait HasDefaultTrait
{
    /**
     * Retrieves the default instance based on the 'default' field.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function getDefault()
    {
        return self::where('default', 1)->first();
    }
}
