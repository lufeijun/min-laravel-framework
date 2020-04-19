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
}
