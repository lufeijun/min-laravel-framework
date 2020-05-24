<?php


namespace Lufeijun1234\Facades;


use RuntimeException;

abstract class Facade
{
	/**
	 * The application instance being facaded.
	 *
	 * @var Application
	 */
	protected static $app;

	/**
	 * The resolved object instances.
	 *
	 * @var array
	 */
	protected static $resolvedInstance;



	/**
	 * Clear all of the resolved instances.
	 *  清除所有
	 * @return void
	 */
	public static function clearResolvedInstances()
	{
		static::$resolvedInstance = [];
	}

	/**
	 * Clear a resolved facade instance.
	 *  清除
	 * @param  string  $name
	 * @return void
	 */
	public static function clearResolvedInstance($name)
	{
		unset(static::$resolvedInstance[$name]);
	}


	/**
	 * Set the application instance.
	 *  设置变量
	 * @param  Application  $app
	 * @return void
	 */
	public static function setFacadeApplication($app)
	{
		static::$app = $app;
	}



	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 *
	 * @throws \RuntimeException
	 */
	protected static function getFacadeAccessor()
	{
		throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
	}




	/**
	 * Handle dynamic, static calls to the object.
	 *
	 * @param  string  $method
	 * @param  array  $args
	 * @return mixed
	 *
	 * @throws \RuntimeException
	 */
	public static function __callStatic($method, $args)
	{

		$instance = static::getFacadeRoot();

		if (! $instance) {
			throw new RuntimeException('A facade root has not been set.');
		}

		return $instance->$method(...$args);
	}


	/**
	 * Get the root object behind the facade.
	 *
	 * @return mixed
	 */
	public static function getFacadeRoot()
	{
		return static::resolveFacadeInstance(static::getFacadeAccessor());
	}


	/**
	 * Resolve the facade root instance from the container.
	 *  解析类
	 * @param  object|string  $name
	 * @return mixed
	 */
	protected static function resolveFacadeInstance($name)
	{
		if (is_object($name)) {
			return $name;
		}

		if (isset(static::$resolvedInstance[$name])) {
			return static::$resolvedInstance[$name];
		}

		if (static::$app) {
			return static::$resolvedInstance[$name] = static::$app[$name];
		}
	}


}
