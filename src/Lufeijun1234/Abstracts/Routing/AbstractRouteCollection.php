<?php


namespace Lufeijun1234\Abstracts\Routing;

use Lufeijun1234\Routing\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Lufeijun1234\Http\Request;
use Lufeijun1234\Routing\Router;

abstract class AbstractRouteCollection
{


	/**
	 * Determine if a route in the array matches the request.
	 *  自己重写的
	 * @param  Route[]  $routes
	 * @param  Request  $request
	 * @param  bool  $includingMethod
	 * @return Route|null
	 */
	protected function matchAgainstRoutes(array $routes, $request, $includingMethod = true)
	{
		foreach ( $routes as $route )
		{
			if ( $route->matches($request, $includingMethod) ) {
				return $route;
			}
		}

//		[$fallbacks, $routes] = collect($routes)->partition(function ($route) {
//			return $route->isFallback;
//		});
//
//		return $routes->merge($fallbacks)->first(function (Route $route) use ($request, $includingMethod) {
//			return $route->matches($request, $includingMethod);
//		});
	}




	/**
	 * Handle the matched route.
	 *
	 * @param  Request  $request
	 * @param  Route|null  $route
	 * @return Route
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	protected function handleMatchedRoute(Request $request, $route)
	{
		if (! is_null($route)) {
			return $route->bind($request);
		}

		// 找找其他方法
		$others = $this->checkForAlternateVerbs($request);
//
		if (count($others) > 0) {
			return $this->getRouteForMethods($request, $others);
		}

		throw new NotFoundHttpException;
	}


	/**
	 * Determine if any routes match on another HTTP verb.
	 *
	 * @param  Request  $request
	 * @return array
	 */
	protected function checkForAlternateVerbs($request)
	{
		$methods = array_diff(Router::$verbs, [$request->getMethod()]);

		// 找其他，ex：如果是 GET，但是没有找到路由，那么就在 POST、PUT 等方法中找找，看有没有
		return array_values(array_filter(
			$methods,
			function ($method) use ($request) {
				return ! is_null($this->matchAgainstRoutes($this->get($method), $request, false));
			}
		));
	}


	/**
	 * Get a route (if necessary) that responds when other available methods are present.
	 *
	 * @param  Request  $request
	 * @param  string[]  $methods
	 * @return Route
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
	 */
	protected function getRouteForMethods($request, array $methods)
	{
		if ($request->method() === 'OPTIONS') {
			return (new Route('OPTIONS', $request->path(), function () use ($methods) {
				return new Response('', 200, ['Allow' => implode(',', $methods)]);
			}))->bind($request);
		}

		$this->methodNotAllowed($methods, $request->method());
	}


	/**
	 * Throw a method not allowed HTTP exception.
	 *
	 * @param  array  $others
	 * @param  string  $method
	 * @return void
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
	 */
	protected function methodNotAllowed(array $others, $method)
	{
		throw new MethodNotAllowedHttpException(
			$others,
			sprintf(
				'The %s method is not supported for this route. Supported methods: %s.',
				$method,
				implode(', ', $others)
			)
		);
	}



}
