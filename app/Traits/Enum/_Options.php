<?php

namespace App\Traits\Enum;

use BackedEnum;
use App\Http\Helpers\RegexHelper;
use Illuminate\Support\Collection;

trait _Options
{

    /** 
     * Get an associative array of [case value => case name].
     * 
     * @param  array       $excludes  An array of constants not to return. The items in the array should be an instance of BackedEnum
     * @return Collection
     * 
     * */
    public static function _options(array $excludes = []): Collection
    {
        $cases = static::cases();

        if (isset($cases[0]) && $cases[0] instanceof BackedEnum) {
            $cases = array_column($cases, 'value', 'name');
        } else {
            $cases = array_column($cases, 'value');
        }

        if (count($excludes)) { // Don't return the set constants
            foreach ($excludes as $exclude) {
                if ($exclude instanceof BackedEnum) {
                    if ($_exclude = get_class()::tryFrom($exclude->value)) {
                        unset($cases[$_exclude->name]);
                    }
                }
            }
        }

        return static::toLableValuePair($cases);
    }

    /**
     * Convert 
     * 
     * @param  Array      $array
     * @return Collection
     */
    private static function toLableValuePair(array $array): Collection
    {
        return collect($array)->map(function ($value, $key) {
            if (method_exists(get_class(), 'exceptions') && in_array($key, array_keys(static::exceptions()))) {
                $label = static::exceptions()[$key];
            } else {
                $label = RegexHelper::format($key);
            }

            return [
                'label' => $label,
                'value' => $value
            ];
        })->values();
    }

    /**
     * Format the name 
     * 
     * @return string
     */
    public function formattedName(): string
    {
        return RegexHelper::format($this->name);
    }
}
