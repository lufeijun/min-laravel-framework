<?php

use Lufeijun1234\Container\Container;
use Lufeijun1234\Support\Arr;
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


if (! function_exists('with')) {
	/**
	 * Return the given value, optionally passed through the given callback.
	 *  回调一下
	 * @param  mixed  $value
	 * @param  callable|null  $callback
	 * @return mixed
	 */
	function with($value, callable $callback = null)
	{
		return is_null($callback) ? $value : $callback($value);
	}
}

if (! function_exists('head')) {
	/**
	 * Get the first element of an array. Useful for method chaining.
	 *
	 * @param  array  $array
	 * @return mixed
	 */
	function head($array)
	{
		return reset($array);
	}
}

if (! function_exists('data_set')) {
	/**
	 * Set an item on an array or object using dot notation.
	 *
	 * @param  mixed  $target
	 * @param  string|array  $key
	 * @param  mixed  $value
	 * @param  bool  $overwrite
	 * @return mixed
	 */
	function data_set(&$target, $key, $value, $overwrite = true)
	{
		$segments = is_array($key) ? $key : explode('.', $key);

		if (($segment = array_shift($segments)) === '*') {
			if (! Arr::accessible($target)) {
				$target = [];
			}

			if ($segments) {
				foreach ($target as &$inner) {
					data_set($inner, $segments, $value, $overwrite);
				}
			} elseif ($overwrite) {
				foreach ($target as &$inner) {
					$inner = $value;
				}
			}
		} elseif (Arr::accessible($target)) {
			if ($segments) {
				if (! Arr::exists($target, $segment)) {
					$target[$segment] = [];
				}

				data_set($target[$segment], $segments, $value, $overwrite);
			} elseif ($overwrite || ! Arr::exists($target, $segment)) {
				$target[$segment] = $value;
			}
		} elseif (is_object($target)) {
			if ($segments) {
				if (! isset($target->{$segment})) {
					$target->{$segment} = [];
				}

				data_set($target->{$segment}, $segments, $value, $overwrite);
			} elseif ($overwrite || ! isset($target->{$segment})) {
				$target->{$segment} = $value;
			}
		} else {
			$target = [];

			if ($segments) {
				data_set($target[$segment], $segments, $value, $overwrite);
			} elseif ($overwrite) {
				$target[$segment] = $value;
			}
		}

		return $target;
	}
}

if (! function_exists('data_get')) {
  /**
   * Get an item from an array or object using "dot" notation.
   * 自己设计了一种格式数组格式
   * @param  mixed  $target
   * @param  string|array|int|null  $key
   * @param  mixed  $default
   * @return mixed
   */
  function data_get($target, $key, $default = null)
  {
    if (is_null($key)) {
      return $target;
    }

    $key = is_array($key) ? $key : explode('.', $key);

    foreach ($key as $i => $segment) {
      unset($key[$i]);

      if (is_null($segment)) {
        return $target;
      }

      if ($segment === '*') {
        if (0 && $target instanceof Collection) {
          $target = $target->all();
        } elseif (! is_array($target)) {
          return value($default);
        }

        $result = [];

        foreach ($target as $item) {
          $result[] = data_get($item, $key);
        }

        return in_array('*', $key) ? Arr::collapse($result) : $result;
      }

      if (Arr::accessible($target) && Arr::exists($target, $segment)) {
        $target = $target[$segment];
      } elseif (is_object($target) && isset($target->{$segment})) {
        $target = $target->{$segment};
      } else {
        return value($default);
      }
    }

    return $target;
  }
}


if (! function_exists('app_path')) {
  /**
   * Get the path to the application folder.
   *
   * @param  string  $path
   * @return string
   */
  function app_path($path = '')
  {
    return app()->path($path);
  }
}
