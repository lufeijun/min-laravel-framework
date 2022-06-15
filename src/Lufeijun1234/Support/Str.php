<?php


namespace Lufeijun1234\Support;


class Str
{
	/**
	 * Determine if a given string contains a given substring.
	 *  判断字符串的包含
	 * @param  string  $haystack
	 * @param  string|string[]  $needles
	 * @return bool
	 */
	public static function contains($haystack, $needles)
	{
		foreach ((array) $needles as $needle) {
			if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Parse a Class[@]method style callback into class and method.
	 *  解析字符串
	 * @param  string  $callback
	 * @param  string|null  $default
	 * @return array<int, string|null>
	 */
	public static function parseCallback($callback, $default = null)
	{
		return static::contains($callback, '@') ? explode('@', $callback, 2) : [$callback, $default];
	}


	/**
	 * Determine if a given string matches a given pattern.
	 *  正则匹配
	 * @param  string|array  $pattern
	 * @param  string  $value
	 * @return bool
	 */
	public static function is($pattern, $value)
	{
		$patterns = Arr::wrap($pattern);

		if (empty($patterns)) {
			return false;
		}

		foreach ($patterns as $pattern) {
			// If the given value is an exact match we can of course return true right
			// from the beginning. Otherwise, we will translate asterisks and do an
			// actual pattern match against the two strings to see if they match.
			if ($pattern == $value) {
				return true;
			}

			$pattern = preg_quote($pattern, '#');

			// Asterisks are translated into zero-or-more regular expression wildcards
			// to make it convenient to check if the strings starts with the given
			// pattern such as "library/*", making any string check convenient.
			$pattern = str_replace('\*', '.*', $pattern);

			if (preg_match('#^'.$pattern.'\z#u', $value) === 1) {
				return true;
			}
		}

		return false;
	}



	/**
	 * Determine if a given string starts with a given substring.
	 *
	 * @param  string  $haystack
	 * @param  string|string[]  $needles
	 * @return bool
	 */
	public static function startsWith($haystack, $needles)
	{
		foreach ((array) $needles as $needle) {
			if ((string) $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0) {
				return true;
			}
		}

		return false;
	}


  /**
   * Return the remainder of a string after the first occurrence of a given value.
   *
   * @param  string  $subject
   * @param  string  $search
   * @return string
   */
  public static function after($subject, $search)
  {
    return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
  }

}
