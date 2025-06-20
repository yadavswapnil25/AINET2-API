<?php

namespace App\Http\Helpers;

use Str;

class RegexHelper {
	/**
	 * Insert a space before every UpperCase character in a string
	 * 
	 * @param  string label
	 * @return string
	 */
	public static function format(string $label): string
	{
		return trim(Str::replace('_', ' -', preg_replace('/([A-Z])/', ' $1', $label)));
	}
}
