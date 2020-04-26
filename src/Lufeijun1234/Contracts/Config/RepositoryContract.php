<?php


namespace Lufeijun1234\Contracts\Config;


interface RepositoryContract
{
	/**
	 * Determine if the given configuration value exists.
	 *  判断是否存在
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key);

	/**
	 * Get the specified configuration value.
	 *  获取
	 * @param  array|string  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = null);

	/**
	 * Get all of the configuration items for the application.
	 *  获取所有
	 * @return array
	 */
	public function all();

	/**
	 * Set a given configuration value.
	 * 设置
	 * @param  array|string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function set($key, $value = null);

	/**
	 * Prepend a value onto an array configuration value.
	 *  前缀
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function prepend($key, $value);

	/**
	 * Push a value onto an array configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function push($key, $value);
}
