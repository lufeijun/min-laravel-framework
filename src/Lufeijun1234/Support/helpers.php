<?php

use Lufeijun1234\Container\Container;
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


if (! function_exists('config')) {
	/**
	 * Get / set the specified configuration value.
	 *
	 * If an array is passed as the key, we will assume you want to set an array of values.
	 *
	 * @param array|string|null $key
	 * @param mixed $default
	 * @return mixed|\Illuminate\Config\Repository
	 * @throws ReflectionException
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 */
	function config($key = null, $default = null)
	{
		if (is_null($key)) {
			return app('config');
		}

		if (is_array($key)) {
			return app('config')->set($key);
		}

		return app('config')->get($key, $default);
	}
}


if (! function_exists('app')) {
	/**
	 * Get the available container instance.
	 *  获取 APP 实例
	 * @param string|null $abstract
	 * @param array $parameters
	 * @return mixed|Application
	 * @throws ReflectionException
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 */
	function app($abstract = null, array $parameters = [])
	{
		if (is_null($abstract)) {
			return Container::getInstance();
		}

		return Container::getInstance()->make($abstract, $parameters);
	}


	if (! function_exists('value')) {
		/**
		 * Return the default value of the given value.
		 *  闭包问题
		 * @param  mixed  $value
		 * @return mixed
		 */
		function value($value)
		{
			return $value instanceof Closure ? $value() : $value;
		}
	}
}



if (! function_exists('storage_path')) {
	/**
	 * Get the path to the storage folder.
	 *
	 * @param string $path
	 * @return string
	 * @throws ReflectionException
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 */
	function storage_path($path = '')
	{
		return app('path.storage').($path ? DIRECTORY_SEPARATOR.$path : $path);
	}
}


if (! function_exists('class_basename')) {
	/**
	 * Get the class "basename" of the given object / class.
	 *
	 * @param  string|object  $class
	 * @return string
	 */
	function class_basename($class)
	{
		$class = is_object($class) ? get_class($class) : $class;

		return basename(str_replace('\\', '/', $class));
	}
}


if (! function_exists('base_path')) {
	/**
	 * Get the path to the base of the install.
	 *
	 * @param string $path
	 * @return string
	 * @throws ReflectionException
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 */
	function base_path($path = '')
	{
		return app()->basePath($path);
	}
}


if (! function_exists('tap')) {
	/**
	 * Call the given Closure with the given value then return the value.
	 *
	 * @param  mixed  $value
	 * @param  callable|null  $callback
	 * @return mixed
	 */
	function tap($value, $callback = null)
	{
		if (is_null($callback)) {
			return new HigherOrderTapProxy($value);
		}

		$callback($value);

		return $value;
	}
}
