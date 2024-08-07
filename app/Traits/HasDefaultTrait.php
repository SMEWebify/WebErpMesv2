<?php

namespace App\Traits;

trait HasDefaultTrait
{
    /**
     * Retrieves the default instance based on the 'default' field.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function getDefault(array $conditions = [])
    {
        $query = self::where('default', 1);

        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        return $query->first();
    }
}
