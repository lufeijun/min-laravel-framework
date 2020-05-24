<?php


namespace Lufeijun1234\Routing;


use Lufeijun1234\Abstracts\Routing\AbstractRouteCollection;
use Lufeijun1234\Http\Request;
use Lufeijun1234\Support\Arr;

class RouteCollection extends AbstractRouteCollection
{

	/**
	 * A look-up table of routes by their names.
	 *  内存变量表
	 * @var Route[]
	 */
	protected $nameList = [];

	/**
	 * An array of the routes keyed by method.
	 *
	 * @var array
	 */
	protected $routes = [];

	/**
	 * A flattened array of all of the routes.
	 *
	 * @var Route[]
	 */
	protected $allRoutes = [];

	/**
	 * A look-up table of routes by controller action.
	 *
	 * @var \Illuminate\Routing\Route[]
	 */
	protected $actionList = [];



	/**
	 * Refresh the name look-up table.
	 *
	 * This is done in case any names are fluently defined or if routes are overwritten.
	 *
	 * @return void
	 */
	public function refreshNameLookups()
	{
		$this->nameList = [];

		foreach ($this->allRoutes as $route) {
			if ($route->getName()) {
				$this->nameList[$route->getName()] = $route;
			}
		}
	}


	/**
	 * Refresh the action look-up table.
	 *
	 * This is done in case any actions are overwritten with new controllers.
	 *
	 * @return void
	 */
	public function refreshActionLookups()
	{
		$this->actionList = [];

		foreach ($this->allRoutes as $route) {
			if (isset($route->getAction()['controller'])) {
				$this->addToActionList($route->getAction(), $route);
			}
		}
	}


	/**
	 * Add a Route instance to the collection.
	 *  在集合中新增一个路由类
	 * @param  Route  $route
	 * @return Route
	 */
	public function add(Route $route)
	{
		$this->addToCollections($route);

		$this->addLookups($route);

		return $route;
	}



	/**
	 * Add the given route to the arrays of routes.
	 *
	 * @param  Route  $route
	 * @return void
	 */
	protected function addToCollections($route)
	{
		$domainAndUri = $route->getDomain().$route->uri();

		$method = '';
		foreach ($route->methods() as $method) {
			$this->routes[$method][$domainAndUri] = $route;
		}


		$this->allRoutes[$method.$domainAndUri] = $route;
	}



	/**
	 * Add the route to any look-up tables if necessary.
	 *
	 * @param  Route  $route
	 * @return void
	 */
	protected function addLookups($route)
	{
		//
		if ($name = $route->getName()) {
			$this->nameList[$name] = $route;
		}

		//
		$action = $route->getAction();

		if (isset($action['controller'])) {
			$this->addToActionList($action, $route);
		}
	}

	protected function addToActionList($action, $route)
	{
		$this->actionList[trim($action['controller'], '\\')] = $route;
	}



	/// 路由寻址部分

	/**
	 * Find the first route matching a given request.
	 *
	 * @param  Request  $request
	 * @return Route
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function match(Request $request)
	{
		// 依据请求方法获取该类型下所有路由，ex：如果是 GET 请求，会返回所有的 GET 路由类，方便下边查询
		$routes = $this->get($request->getMethod());

		// 寻找路由
		$route = $this->matchAgainstRoutes($routes, $request);

		return $this->handleMatchedRoute($request, $route);
	}

	/**
	 * Get routes from the collection by method.
	 *  获取路由集合
	 * @param  string|null  $method
	 * @return Route[]
	 */
	public function get($method = null)
	{
		return is_null($method) ? $this->getRoutes() : Arr::get($this->routes, $method, []);
	}


	/**
	 * Get all of the routes in the collection.
	 *  获取所有路由
	 * @return Route[]
	 */
	public function getRoutes()
	{
		return array_values($this->allRoutes);
	}


}
