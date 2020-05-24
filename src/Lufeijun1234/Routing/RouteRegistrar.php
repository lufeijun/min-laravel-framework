<?php


namespace Lufeijun1234\Routing;


use BadMethodCallException;
use InvalidArgumentException;
use Lufeijun1234\Support\Arr;

class RouteRegistrar
{
	/**
	 * The router instance.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * The attributes to pass on to the router.
	 *  属性
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * The methods to dynamically pass through to the router.
	 *  动态处理的方法集合
	 * @var array
	 */
	protected $passthru = [
		'get', 'post', 'put', 'patch', 'delete', 'options', 'any',
	];

	/**
	 * The attributes that can be set through this class.
	 *  合法的属性值
	 * @var array
	 */
	protected $allowedAttributes = [
		'as', 'domain', 'middleware', 'name', 'namespace', 'prefix', 'where',
	];

	/**
	 * The attributes that are aliased.
	 *  属性别名
	 * @var array
	 */
	protected $aliases = [
		'name' => 'as',
	];



	/**
	 * Create a new route registrar instance.
	 *
	 * @param Router  $router
	 * @return void
	 */
	public function __construct(Router $router)
	{
		$this->router = $router;
	}


	/**
	 * Set the value for a given attribute.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return $this
	 *
	 * @throws \InvalidArgumentException
	 */
	public function attribute($key, $value)
	{
		if (! in_array($key, $this->allowedAttributes)) {
			throw new InvalidArgumentException("Attribute [{$key}] does not exist.");
		}

		$this->attributes[Arr::get($this->aliases, $key, $key)] = $value;

		return $this;
	}

	/**
	 * Create a route group with shared attributes.
	 *
	 * @param  \Closure|string  $callback
	 * @return void
	 */
	public function group($callback)
	{
		$this->router->group($this->attributes, $callback);
	}



	/**
	 * Register a new route with the router.
	 *
	 * @param  string  $method
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $action
	 * @return Route
	 */
	protected function registerRoute($method, $uri, $action = null)
	{
		if (! is_array($action)) {
			$action = array_merge($this->attributes, $action ? ['uses' => $action] : []);
		}

		return $this->router->{$method}($uri, $this->compileAction($action));
	}

	protected function compileAction($action)
	{
		if (is_null($action)) {
			return $this->attributes;
		}

		if (is_string($action) || $action instanceof Closure) {
			$action = ['uses' => $action];
		}

		return array_merge($this->attributes, $action);
	}



	/**
	 * Dynamically handle calls into the route registrar.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return \Illuminate\Routing\Route|$this
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $parameters)
	{
		if (in_array($method, $this->passthru)) {
			return $this->registerRoute($method, ...$parameters);
		}

		if (in_array($method, $this->allowedAttributes)) {
			if ($method === 'middleware') {
				return $this->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
			}

			return $this->attribute($method, $parameters[0]);
		}

		throw new BadMethodCallException(sprintf(
			'Method %s::%s does not exist.', static::class, $method
		));
	}

}
