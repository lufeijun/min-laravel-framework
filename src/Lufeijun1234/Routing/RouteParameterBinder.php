<?php


namespace Lufeijun1234\Routing;


use Lufeijun1234\Support\Arr;

class RouteParameterBinder
{
	/**
	 * The route instance.
	 *
	 * @var Route
	 */
	protected $route;

	/**
	 * Create a new Route parameter binder instance.
	 *
	 * @param  Route  $route
	 * @return void
	 */
	public function __construct($route)
	{
		$this->route = $route;
	}



	/**
	 * Get the parameters for the route.
	 *
	 * @param  Request  $request
	 * @return array
	 */
	public function parameters($request)
	{
		$parameters = $this->bindPathParameters($request);


		//
		if (! is_null($this->route->compiled->getHostRegex())) {
			$parameters = $this->bindHostParameters(
				$request, $parameters
			);
		}

		return $this->replaceDefaults($parameters);
	}


	/**
	 * Get the parameter matches for the path portion of the URI.
	 *
	 * @param  Request  $request
	 * @return array
	 */
	protected function bindPathParameters($request)
	{
		$path = '/'.ltrim($request->decodedPath(), '/');

		preg_match($this->route->compiled->getRegex(), $path, $matches);

		return $this->matchToKeys(array_slice($matches, 1));
	}


	/**
	 * Combine a set of parameter matches with the route's keys.
	 *
	 * @param  array  $matches
	 * @return array
	 */
	protected function matchToKeys(array $matches)
	{
		if (empty($parameterNames = $this->route->parameterNames())) {
			return [];
		}

		$parameters = array_intersect_key($matches, array_flip($parameterNames));

		return array_filter($parameters, function ($value) {
			return is_string($value) && strlen($value) > 0;
		});
	}


	/**
	 * Replace null parameters with their defaults.
	 *
	 * @param  array  $parameters
	 * @return array
	 */
	protected function replaceDefaults(array $parameters)
	{
		foreach ($parameters as $key => $value) {
			$parameters[$key] = $value ?? Arr::get($this->route->defaults, $key);
		}

		foreach ($this->route->defaults as $key => $value) {
			if (! isset($parameters[$key])) {
				$parameters[$key] = $value;
			}
		}

		return $parameters;
	}

}
