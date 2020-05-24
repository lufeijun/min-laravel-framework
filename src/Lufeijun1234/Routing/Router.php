<?php


namespace Lufeijun1234\Routing;


use Closure;
use Lufeijun1234\Container\Container;
use Lufeijun1234\Contracts\Events\DispatcherContract;
use Lufeijun1234\Contracts\Support\Responsable;
use Lufeijun1234\Http\Request;
use Lufeijun1234\Http\Response;
use Lufeijun1234\Traits\Macroable;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;


class Router
{

	use Macroable {
		__call as macroCall;
	}

	/**
	 * The event dispatcher instance.
	 *  事件机制
	 * @var
	 */
	protected $events;

	/**
	 * The IoC container instance.
	 *  容器
	 * @var Container
	 */
	protected $container;

	/**
	 * The route collection instance.
	 *   路由集合
	 * @var
	 */
	protected $routes;



	/**
	 * The route group attribute stack.
	 *  在解析的时候，因为会有嵌套，所以需要维护一个栈
	 * @var array
	 */
	protected $groupStack = [];


	/**
	 * The globally available parameter patterns.
	 *
	 * @var array
	 */
	protected $patterns = [];


	/**
	 * The request currently being dispatched.
	 *
	 * @var Request
	 */
	protected $currentRequest;

	/**
	 * The currently dispatched route instance.
	 *  当前路由类
	 * @var Route|null
	 */
	protected $current;


	/**
	 * All of the verbs supported by the router.
	 *
	 * @var array
	 */
	public static $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];




	// 构造函数
	public function  __construct(DispatcherContract $events, Container $container = null)
	{
		$this->events = $events;
		$this->routes = new RouteCollection;
		$this->container = $container ?: new Container;
	}



	/**
	 * Register a new GET route with the router.
	 *  get 请求
	 * @param  string  $uri
	 * @param  array|string|callable|null  $action
	 * @return \Illuminate\Routing\Route
	 */
	public function get($uri, $action = null)
	{
		return $this->addRoute(['GET', 'HEAD'], $uri, $action);
	}



	// 获取 RouteCollection
	public function getRoutes()
	{
		return $this->routes;
	}



	/**
	 * Prefix the given URI with the last prefix.
	 *  前缀
	 * @param  string  $uri
	 * @return string
	 */
	protected function prefix($uri)
	{
		return trim(trim($this->getLastGroupPrefix(), '/').'/'.trim($uri, '/'), '/') ?: '/';
	}


	/**
	 * Create a route group with shared attributes.
	 *  路由组
	 * @param  array  $attributes
	 * @param  \Closure|string  $routes
	 * @return void
	 */
	public function group(array $attributes, $routes)
	{
		// 获取栈信息，并且将当前信息进栈
		$this->updateGroupStack($attributes);

		// 创建路由类 route
		$this->loadRoutes($routes);

		// 解析完了之后需要出栈。
		array_pop($this->groupStack);
	}


	/**
	 * Update the group stack with the given attributes.
	 *  更新组栈
	 * @param  array  $attributes
	 * @return void
	 */
	protected function updateGroupStack(array $attributes)
	{
		if ($this->hasGroupStack()) {
			$attributes = $this->mergeWithLastGroup($attributes);
		}

		$this->groupStack[] = $attributes;
	}


	/**
	 * Get the prefix from the last group on the stack.
	 *  获取组栈信息
	 * @return string
	 */
	public function getLastGroupPrefix()
	{
		if ($this->hasGroupStack()) {
			$last = end($this->groupStack);

			return $last['prefix'] ?? '';
		}

		return '';
	}


	/**
	 * Determine if the router currently has a group stack.
	 *
	 * @return bool
	 */
	public function hasGroupStack()
	{
		return ! empty($this->groupStack);
	}


	/**
	 * Merge the given array with the last group stack.
	 *  合并一些信息
	 * @param  array  $new
	 * @param  bool  $prependExistingPrefix
	 * @return array
	 */
	public function mergeWithLastGroup($new, $prependExistingPrefix = true)
	{
		return RouteGroup::merge($new, end($this->groupStack), $prependExistingPrefix);
	}


	/**
	 * Load the provided routes.
	 *  加载路由信息 ， string 表示一个文件地址或者控制器
	 * @param  \Closure|string  $routes
	 * @return void
	 */
	protected function loadRoutes($routes)
	{
		if ($routes instanceof Closure) {
			$routes($this);
		} else {
			(new RouteFileRegistrar($this))->register($routes);
		}
	}



	/**
	 * Add a route to the underlying route collection.
	 *  添加一条路由
	 * @param  array|string  $methods
	 * @param  string  $uri
	 * @param  array|string|callable|null  $action
	 * @return \Illuminate\Routing\Route
	 */
	public function addRoute($methods, $uri, $action)
	{
		return $this->routes->add($this->createRoute($methods, $uri, $action));
	}


	/**
	 * Create a new route instance.
	 *  创建一个路由类 Route
	 * @param  array|string  $methods
	 * @param  string  $uri
	 * @param  mixed  $action
	 * @return Route
	 */
	protected function createRoute($methods, $uri, $action)
	{

		// 如果action 是一个控制器，那么就会尝试去解析他
		if ($this->actionReferencesController($action)) {
			$action = $this->convertToControllerAction($action);
		}

		// 初始化路由类
		$route = $this->newRoute(
			$methods, $this->prefix($uri), $action
		);


		// 合并处理一写属性
		if ($this->hasGroupStack()) {
			$this->mergeGroupAttributesIntoRoute($route);
		}


		// 正则条件
		$this->addWhereClausesToRoute($route);

		return $route;
	}



	/**
	 * Determine if the action is routing to a controller.
	 *  判断路由对应的处理器是不是控制器
	 * @param  mixed  $action
	 * @return bool
	 */
	protected function actionReferencesController($action)
	{
		if (! $action instanceof Closure) {
			return is_string($action) || (isset($action['uses']) && is_string($action['uses']));
		}

		return false;
	}


	/**
	 * Add a controller based route action to the action array.
	 *  添加控制器
	 * @param  array|string  $action
	 * @return array
	 */
	protected function convertToControllerAction($action)
	{
		if (is_string($action)) {
			$action = ['uses' => $action];
		}



		// 合并的问题
		if ($this->hasGroupStack()) {
			$action['uses'] = $this->prependGroupNamespace($action['uses']);
		}

		// 备份，等待使用
		$action['controller'] = $action['uses'];

		return $action;
	}

	/**
	 * Prepend the last group namespace onto the use clause.
	 *
	 * @param  string  $class
	 * @return string
	 */
	protected function prependGroupNamespace($class)
	{
		$group = end($this->groupStack);

		return isset($group['namespace']) && strpos($class, '\\') !== 0
			? $group['namespace'].'\\'.$class : $class;
	}



	/**
	 * Create a new Route object.
	 *  创建路由对象
	 * @param  array|string  $methods
	 * @param  string  $uri
	 * @param  mixed  $action
	 * @return Route
	 */
	public function newRoute($methods, $uri, $action)
	{
		return (new Route($methods, $uri, $action))
			->setRouter($this)
			->setContainer($this->container);
	}


	/**
	 * Merge the group stack with the controller action.
	 *
	 * @param  Route  $route
	 * @return void
	 */
	protected function mergeGroupAttributesIntoRoute($route)
	{
		$route->setAction($this->mergeWithLastGroup(
			$route->getAction(),
			$prependExistingPrefix = false
		));
	}


	/**
	 * Add the necessary where clauses to the route based on its initial registration.
	 *
	 * @param  Route  $route
	 * @return  Route
	 */
	protected function addWhereClausesToRoute($route)
	{
		$route->where(array_merge(
			$this->patterns, $route->getAction()['where'] ?? []
		));

		return $route;
	}




	/// 路由寻址部分

	public function dispatch(Request $request)
	{
		$this->currentRequest = $request;

		return $this->dispatchToRoute($request);
	}


	/**
	 * Dispatch the request to a route and return the response.
	 *
	 * @param  Request  $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function dispatchToRoute(Request $request)
	{
		return $this->runRoute($request, $this->findRoute($request));
	}


	/**
	 * Find the route matching a given request.
	 *	寻找路由类
	 * @param  Request  $request
	 * @return Route
	 */
	protected function findRoute($request)
	{
		$this->current = $route = $this->routes->match($request);

		$this->container->instance(Route::class, $route);

		return $route;
	}


	/**
	 * Return the response for the given route.
	 *
	 * @param  Request  $request
	 * @param  Route  $route
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function runRoute(Request $request, Route $route)
	{
		$request->setRouteResolver(function () use ($route) {
			return $route;
		});

		// 解析事件
		// $this->events->dispatch(new RouteMatched($route, $request));

		return $this->prepareResponse($request,
			$this->runRouteWithinStack($route, $request)
		);
	}


	/**
	 * Run the given route within a Stack "onion" instance.
	 *
	 * @param Route $route
	 * @param Request $request
	 * @return mixed
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws \ReflectionException
	 */
	protected function runRouteWithinStack(Route $route, Request $request)
	{
		// 判断中间件
		$shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
			$this->container->make('middleware.disable') === true;


		$middleware = $shouldSkipMiddleware ? [] : $this->gatherRouteMiddleware($route);

		return $this->prepareResponse(
			$request, $route->run()
		);

//		return (new Pipeline($this->container))
//			->send($request)
//			->through($middleware)
//			->then(function ($request) use ($route) {
//				return $this->prepareResponse(
//					$request, $route->run()
//				);
//			});
	}


	/**
	 * Gather the middleware for the given route with resolved class names.
	 *  收集中间件
	 * @param  Route  $route
	 * @return array
	 */
	public function gatherRouteMiddleware(Route $route)
	{
		return [];
		$excluded = collect($route->excludedMiddleware())->map(function ($name) {
			return (array) MiddlewareNameResolver::resolve($name, $this->middleware, $this->middlewareGroups);
		})->flatten()->values()->all();

		$middleware = collect($route->gatherMiddleware())->map(function ($name) {
			return (array) MiddlewareNameResolver::resolve($name, $this->middleware, $this->middlewareGroups);
		})->flatten()->reject(function ($name) use ($route, $excluded) {
			return in_array($name, $excluded, true);
		})->values();

		return $this->sortMiddleware($middleware);
	}


	/**
	 * Create a response instance from the given value.
	 *  生成响应
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  mixed  $response
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function prepareResponse($request, $response)
	{
		return static::toResponse($request, $response);
	}

	/**
	 * Static version of prepareResponse.
	 *  响应
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  mixed  $response
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public static function toResponse($request, $response)
	{
		if ($response instanceof Responsable) {
			$response = $response->toResponse($request);
		}

		if ($response instanceof PsrResponseInterface) {
			$response = (new HttpFoundationFactory)->createResponse($response);
		} elseif ($response instanceof Model && $response->wasRecentlyCreated) {
			$response = new JsonResponse($response, 201);
		} elseif (! $response instanceof SymfonyResponse &&
			($response instanceof Arrayable ||
				$response instanceof Jsonable ||
				$response instanceof ArrayObject ||
				$response instanceof JsonSerializable ||
				is_array($response))) {
			$response = new JsonResponse($response);
		} elseif (! $response instanceof SymfonyResponse) {
			$response = new Response($response, 200, ['Content-Type' => 'text/html']);
		}

		if ($response->getStatusCode() === Response::HTTP_NOT_MODIFIED) {
			$response->setNotModified();
		}

		return $response->prepare($request);
	}




	/**
	 * Dynamically handle calls into the router instance.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (static::hasMacro($method)) {
			return $this->macroCall($method, $parameters);
		}

		if ($method === 'middleware') {
			return (new RouteRegistrar($this))->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
		}

		return (new RouteRegistrar($this))->attribute($method, $parameters[0]);
	}

}
