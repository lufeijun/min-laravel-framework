<?php


namespace Lufeijun1234\Routing;

use Lufeijun1234\Container\Container;
use Lufeijun1234\Contracts\Routing\Controller;
use Lufeijun1234\Contracts\Routing\ControllerDispatcher as ControllerDispatcherContract;
use Lufeijun1234\Traits\RouteDependencyResolverTrait;


class ControllerDispatcher implements ControllerDispatcherContract
{

	use RouteDependencyResolverTrait;

	/**
	 * The container instance.
	 *
	 * @var Container
	 */
	protected $container;



	public function __construct(Container $container)
	{
		$this->container = $container;
	}


	/**
	 * Dispatch a request to a given controller and method.
	 *
	 * @param  Route  $route
	 * @param  mixed  $controller
	 * @param  string  $method
	 * @return mixed
	 */
	public function dispatch(Route $route, $controller, $method)
	{
		$parameters = $this->resolveClassMethodDependencies(
			$route->parametersWithoutNulls(), $controller, $method
		);

		if (method_exists($controller, 'callAction')) {
			return $controller->callAction($method, $parameters);
		}

		return $controller->{$method}(...array_values($parameters));
	}

	/**
	 * @inheritDoc
	 */
	public function getMiddleware($controller, $method)
	{
		// TODO: Implement getMiddleware() method.
	}
}
