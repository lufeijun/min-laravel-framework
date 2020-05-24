<?php


namespace Lufeijun1234\Routing;


use LogicException;
use Lufeijun1234\Routing\Matching\UriValidator;
use Lufeijun1234\Container\Container;
use Lufeijun1234\Http\Request;
use Lufeijun1234\Routing\Matching\HostValidator;
use Lufeijun1234\Routing\Matching\MethodValidator;
use Lufeijun1234\Routing\Matching\SchemeValidator;
use Lufeijun1234\Support\Arr;


use Lufeijun1234\Support\Str;
use Lufeijun1234\Traits\RouteDependencyResolverTrait;
use ReflectionFunction;
use Symfony\Component\Routing\Route as SymfonyRoute;

use Lufeijun1234\Contracts\Routing\ControllerDispatcher as ControllerDispatcherContract;



class Route
{
	use RouteDependencyResolverTrait;


	/**
	 * The URI pattern the route responds to.
	 *
	 * @var string
	 */
	public $uri;

	/**
	 * The HTTP methods the route responds to.
	 *
	 * @var array
	 */
	public $methods;

	/**
	 * The route action array.
	 *
	 * @var array
	 */
	public $action;

	/**
	 * The controller instance.
	 *
	 * @var mixed
	 */
	public $controller;


	/**
	 * The router instance used by the route.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * The container instance used by the route.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * The fields that implicit binding should use for a given parameter.
	 *
	 * @var array
	 */
	protected $bindingFields = [];


	/**
	 * Indicates whether the route is a fallback route.
	 *  回调函数
	 * @var bool
	 */
	public $isFallback = false;


	/**
	 * The compiled version of the route.
	 *
	 * @var \Symfony\Component\Routing\CompiledRoute
	 */
	public $compiled;

	/**
	 * The regular expression requirements.
	 *
	 * @var array
	 */
	public $wheres = [];


	/**
	 * The validators used by the routes.
	 *
	 * @var array
	 */
	public static $validators;


	/**
	 * The array of matched parameters.
	 *
	 * @var array
	 */
	public $parameters;


	/**
	 * The parameter names for the route.
	 *
	 * @var array|null
	 */
	public $parameterNames;

	/**
	 * The array of the matched parameters' original values.
	 *
	 * @var array
	 */
	protected $originalParameters;



	/**
	 * The default values for the route.
	 *
	 * @var array
	 */
	public $defaults = [];



	/**
	 * Create a new Route instance.
	 *  初始化函数
	 * @param  array|string  $methods
	 * @param  string  $uri
	 * @param  \Closure|array  $action
	 * @return void
	 */
	public function __construct($methods, $uri, $action)
	{
		$this->uri = $uri;
		$this->methods = (array) $methods;
		$this->action = Arr::except($this->parseAction($action), ['prefix']);

		// 这一步不知道有什么用处
		if (in_array('GET', $this->methods) && ! in_array('HEAD', $this->methods)) {
			$this->methods[] = 'HEAD';
		}

		$this->prefix(is_array($action) ? Arr::get($action, 'prefix') : '');
	}



	/**
	 * Parse the route action into a standard array.
	 *  解析 action
	 * @param  callable|array|null  $action
	 * @return array
	 *
	 * @throws \UnexpectedValueException
	 */
	protected function parseAction($action)
	{
		return RouteAction::parse($this->uri, $action);
	}


	/**
	 * Add a prefix to the route URI.
	 *  路由前缀
	 * @param  string  $prefix
	 * @return $this
	 */
	public function prefix($prefix)
	{
		$this->updatePrefixOnAction($prefix);

		$uri = rtrim($prefix, '/').'/'.ltrim($this->uri, '/');

		return $this->setUri($uri !== '/' ? trim($uri, '/') : $uri);
	}

	/**
	 * Update the "prefix" attribute on the action array.
	 *
	 * @param  string  $prefix
	 * @return void
	 */
	protected function updatePrefixOnAction($prefix)
	{
		if (! empty($newPrefix = trim(rtrim($prefix, '/').'/'.ltrim($this->action['prefix'] ?? '', '/'), '/'))) {
			$this->action['prefix'] = $newPrefix;
		}
	}



	/**
	 * Set the URI that the route responds to.
	 *  设置 URI
	 * @param  string  $uri
	 * @return $this
	 */
	public function setUri($uri)
	{

		$this->uri = $this->parseUri($uri);

		return $this;
	}

	/**
	 * Get the URI associated with the route.
	 *
	 * @return string
	 */
	public function uri()
	{
		return $this->uri;
	}

	/**
	 * Parse the route URI and normalize / store any implicit binding fields.
	 *  解析 uri
	 * @param  string  $uri
	 * @return string
	 */
	protected function parseUri($uri)
	{
		$this->bindingFields = [];

		return tap(RouteUri::parse($uri), function ($uri) {
			$this->bindingFields = $uri->bindingFields;
		})->uri;
	}




	/**
	 * Set the router instance on the route.
	 *
	 * @param  Router  $router
	 * @return $this
	 */
	public function setRouter(Router $router)
	{
		$this->router = $router;

		return $this;
	}

	/**
	 * Set the container instance on the route.
	 *
	 * @param  Container  $container
	 * @return $this
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;

		return $this;
	}


	/**
	 * Set the action array for the route.
	 *
	 * @param  array  $action
	 * @return $this
	 */
	public function setAction(array $action)
	{
		$this->action = $action;

		return $this;
	}

	public function getAction($key = null)
	{
		return Arr::get($this->action, $key);
	}



	/**
	 * Get the name of the route instance.
	 *  获取 name 别名
	 * @return string|null
	 */
	public function getName()
	{
		return $this->action['as'] ?? null;
	}



	/**
	 * Set a regular expression requirement on the route.
	 *
	 * @param  array|string  $name
	 * @param  string|null  $expression
	 * @return $this
	 */
	public function where($name, $expression = null)
	{
		foreach ($this->parseWhere($name, $expression) as $name => $expression) {
			$this->wheres[$name] = $expression;
		}

		return $this;
	}

	/**
	 * Parse arguments to the where method into an array.
	 *
	 * @param  array|string  $name
	 * @param  string  $expression
	 * @return array
	 */
	protected function parseWhere($name, $expression)
	{
		return is_array($name) ? $name : [$name => $expression];
	}


	/**
	 * Get the domain defined for the route.
	 *
	 * @return string|null
	 */
	public function getDomain()
	{
		return isset($this->action['domain'])
			? str_replace(['http://', 'https://'], '', $this->action['domain']) : null;
	}


	/**
	 * Mark this route as a fallback route.
	 *
	 * @return $this
	 */
	public function fallback()
	{
		$this->isFallback = true;

		return $this;
	}

	/**
	 * Set the fallback value.
	 *
	 * @param  bool  $isFallback
	 * @return $this
	 */
	public function setFallback($isFallback)
	{
		$this->isFallback = $isFallback;

		return $this;
	}


	/**
	 * Get the HTTP verbs the route responds to.
	 *
	 * @return array
	 */
	public function methods()
	{
		return $this->methods;
	}



	//// 路由寻址部分

	/**
	 * Determine if the route matches a given request.
	 *
	 * @param  Request  $request
	 * @param  bool  $includingMethod
	 * @return bool
	 */
	public function matches(Request $request, $includingMethod = true)
	{
		$this->compileRoute();


		foreach ($this->getValidators() as $validator) {
			if (! $includingMethod && $validator instanceof MethodValidator) {
				continue;
			}

			if (! $validator->matches($this, $request)) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Compile the route into a Symfony CompiledRoute instance.
	 *
	 * @return \Symfony\Component\Routing\CompiledRoute
	 */
	protected function compileRoute()
	{
		if (! $this->compiled) {
			$this->compiled = $this->toSymfonyRoute()->compile();
		}

		return $this->compiled;
	}

	/**
	 * Get the compiled version of the route.
	 *
	 * @return \Symfony\Component\Routing\CompiledRoute
	 */
	public function getCompiled()
	{
		return $this->compiled;
	}


	/**
	 * Convert the route to a Symfony route.
	 *
	 * @return \Symfony\Component\Routing\Route
	 */
	public function toSymfonyRoute()
	{
		return new SymfonyRoute(
			preg_replace('/\{(\w+?)\?\}/', '{$1}', $this->uri()), $this->getOptionalParameterNames(),
			$this->wheres,
			['utf8' => true, 'action' => $this->action],
			$this->getDomain() ?: '',
			[],
			$this->methods
		);
	}

	/**
	 * Get the optional parameter names for the route.
	 *
	 * @return array
	 */
	protected function getOptionalParameterNames()
	{
		preg_match_all('/\{(\w+?)\?\}/', $this->uri(), $matches);

		return isset($matches[1]) ? array_fill_keys($matches[1], null) : [];
	}


	/**
	 * Get the route validators for the instance.
	 *  路由验证规则
	 * @return array
	 */
	public static function getValidators()
	{
		if (isset(static::$validators)) {
			return static::$validators;
		}


		// 规则验证数组，每一个都进行匹配验证
		return static::$validators = [
			new UriValidator, new MethodValidator,
			new SchemeValidator, new HostValidator,
		];
	}



	/**
	 * Determine if the route only responds to HTTP requests.
	 *
	 * @return bool
	 */
	public function httpOnly()
	{
		return in_array('http', $this->action, true);
	}

	/**
	 * Determine if the route only responds to HTTPS requests.
	 *
	 * @return bool
	 */
	public function secure()
	{
		return in_array('https', $this->action, true);
	}



	/**
	 * Bind the route to a given request for execution.
	 *
	 * @param  Request  $request
	 * @return $this
	 */
	public function bind(Request $request)
	{
		$this->compileRoute();

		$this->parameters = (new RouteParameterBinder($this))
			->parameters($request);


		$this->originalParameters = $this->parameters;


		return $this;
	}


	/**
	 * Get all of the parameter names for the route.
	 *
	 * @return array
	 */
	public function parameterNames()
	{
		if (isset($this->parameterNames)) {
			return $this->parameterNames;
		}

		return $this->parameterNames = $this->compileParameterNames();
	}

	/**
	 * Get the parameter names for the route.
	 *
	 * @return array
	 */
	protected function compileParameterNames()
	{
		preg_match_all('/\{(.*?)\}/', $this->getDomain().$this->uri, $matches);

		return array_map(function ($m) {
			return trim($m, '?');
		}, $matches[1]);
	}


	/**
	 * Run the route action and return the response.
	 *
	 * @return mixed
	 */
	public function run()
	{
		$this->container = $this->container ?: new Container;

		try {
			if ($this->isControllerAction()) {
				return $this->runController();
			}
			return $this->runCallable();
		} catch (HttpResponseException $e) {
			return $e->getResponse();
		}
	}

	/**
	 * Checks whether the route's action is a controller.
	 *
	 * @return bool
	 */
	protected function isControllerAction()
	{
		return is_string($this->action['uses']);
	}

	/**
	 * Run the route action and return the response.
	 *  运行闭包
	 * @return mixed
	 * @throws \ReflectionException
	 */
	protected function runCallable()
	{
		$callable = $this->action['uses'];

		return $callable(...array_values($this->resolveMethodDependencies(
			$this->parametersWithoutNulls(), new ReflectionFunction($this->action['uses'])
		)));
	}


	/**
	 * Get the key / value list of parameters without null values.
	 *
	 * @return array
	 */
	public function parametersWithoutNulls()
	{
		return array_filter($this->parameters(), function ($p) {
			return ! is_null($p);
		});
	}


	/**
	 * Get the key / value list of parameters for the route.
	 *
	 * @return array
	 *
	 * @throws \LogicException
	 */
	public function parameters()
	{
		if (isset($this->parameters)) {
			return $this->parameters;
		}

		throw new LogicException('Route is not bound.');
	}


	/**
	 * Run the route action and return the response.
	 *
	 * @return mixed
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	protected function runController()
	{
		return $this->controllerDispatcher()->dispatch(
			$this, $this->getController(), $this->getControllerMethod()
		);
	}

	/**
	 * Get the dispatcher for the route's controller.
	 *  获取控制器
	 * @return ControllerDispatcher
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws \ReflectionException
	 */
	public function controllerDispatcher()
	{
		if ($this->container->bound(ControllerDispatcherContract::class)) {
			return $this->container->make(ControllerDispatcherContract::class);
		}

		return new ControllerDispatcher($this->container);
	}


	/**
	 * Get the controller instance for the route.
	 *
	 * @return mixed
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws \ReflectionException
	 */
	public function getController()
	{
		if (! $this->controller) {
			$class = $this->parseControllerCallback()[0];

			$this->controller = $this->container->make(ltrim($class, '\\'));
		}

		return $this->controller;
	}


	/**
	 * Parse the controller.
	 *
	 * @return array
	 */
	protected function parseControllerCallback()
	{
		return Str::parseCallback($this->action['uses']);
	}


	/**
	 * Get the controller method used for the route.
	 *
	 * @return string
	 */
	protected function getControllerMethod()
	{
		return $this->parseControllerCallback()[1];
	}


}
