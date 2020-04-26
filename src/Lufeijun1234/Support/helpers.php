<?php
use Lufeijun1234\Support\Env;


if (! function_exists('env')) {
	/**
	 * Gets the value of an environment variable.
	 *  获取环境变量值
	 * @param  string  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	function env($key, $default = null)
	{
		return Env::get($key, $default);
	}
}
