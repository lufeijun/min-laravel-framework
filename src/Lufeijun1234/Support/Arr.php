<?php
namespace Lufeijun1234\Support;


use Lufeijun1234\Traits\Macroable;


/**
 * 数组处理的一些方法集合
 * Class Arr
 * @package Lufeijun1234\Support
 */
class Arr
{
	use Macroable;


	/**
	 * Filter the array using the given callback.
	 *  数组过滤
	 * @param  array  $array
	 * @param  callable  $callback
	 * @return array
	 */
	public static function where($array, callable $callback)
	{
		return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
	}


	/**
	 * If the given value is not an array and not null, wrap it in one.
	 *  包装成数组
	 * @param  mixed  $value
	 * @return array
	 */
	public static function wrap($value)
	{
		if (is_null($value)) {
			return [];
		}

		return is_array($value) ? $value : [$value];
	}



	/**
	 * Check if an item or items exist in an array using "dot" notation.
	 *  检测值是否存在，
	 * @param  \ArrayAccess|array  $array
	 * @param  string|array  $keys
	 * @return bool
	 */
	public static function has($array, $keys)
	{
		$keys = (array) $keys;

		if (! $array || $keys === []) {
			return false;
		}

		foreach ($keys as $key) {
			$subKeyArray = $array;

			if (static::exists($array, $key)) {
				continue;
			}

			foreach (explode('.', $key) as $segment) {
				if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
					$subKeyArray = $subKeyArray[$segment];
				} else {
					return false;
				}
			}
		}

		return true;
	}


	/**
	 * Determine if the given key exists in the provided array.
	 *
	 * @param  \ArrayAccess|array  $array
	 * @param  string|int  $key
	 * @return bool
	 */
	public static function exists($array, $key)
	{
		if ($array instanceof ArrayAccess) {
			return $array->offsetExists($key);
		}

		return array_key_exists($key, $array);
	}


	/**
	 * Determine whether the given value is array accessible.
	 *
	 * @param  mixed  $value
	 * @return bool
	 */
	public static function accessible($value)
	{
		return is_array($value) || $value instanceof ArrayAccess;
	}


	/**
	 * Get an item from an array using "dot" notation.
	 *  使用 . 隔开的数组
	 * @param  \ArrayAccess|array  $array
	 * @param  string|int|null  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function get($array, $key, $default = null)
	{
		if (! static::accessible($array)) {
			return value($default);
		}

		if (is_null($key)) {
			return $array;
		}

		if (static::exists($array, $key)) {
			return $array[$key];
		}

		if (strpos($key, '.') === false) {
			return $array[$key] ?? value($default);
		}

		foreach (explode('.', $key) as $segment) {
			if (static::accessible($array) && static::exists($array, $segment)) {
				$array = $array[$segment];
			} else {
				return value($default);
			}
		}

		return $array;
	}


	/**
	 * Set an array item to a given value using "dot" notation.
	 *
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param  array  $array
	 * @param  string|null  $key
	 * @param  mixed  $value
	 * @return array
	 */
	public static function set(&$array, $key, $value)
	{

		if (is_null($key)) {
			return $array = $value;
		}
		// custom.city
		$keys = explode('.', $key);

		foreach ($keys as $i => $key) {
			if (count($keys) === 1) {
				break;
			}

			// unset 掉了，所以当 keys 数组剩余 1 个时，上边的 if 条件成立
			unset($keys[$i]);

			if (! isset($array[$key]) || ! is_array($array[$key])) {
				$array[$key] = [];
			}

			$array = &$array[$key];
		}

		$array[array_shift($keys)] = $value;

		return $array;
	}
}
