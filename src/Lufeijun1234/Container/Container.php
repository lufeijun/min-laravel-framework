<?php
namespace Lufeijun1234\Container;


use ArrayAccess;
use Closure;
use Exception;
use InvalidArgumentException;
use Lufeijun1234\Container\BindingResolutionException;
use Lufeijun1234\Contracts\Container\ContainerContract;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container implements ArrayAccess , ContainerContract
{
	/**
	 * 待定
	 * @var static
	 */
	protected static $instance;

	/**
	 * 所有解析过的类数组
	 *
	 * @var bool[]
	 */
	protected $resolved = [];

	/**
	 * 容器的绑定数组
	 *
	 * @var array[]
	 */
	protected $bindings = [];

	/**
	 * 解析的实体类数组
	 *
	 * @var object[]
	 */
	protected $instances = [];


	/**
	 * 系统注册的别名
	 *
	 * @var string[]
	 */
	protected $aliases = [];

	/**
	 * 也是注册过的别名数组，key 是 abstract 抽象类名称
	 *
	 * @var array[]
	 */
	protected $abstractAliases = [];


	/**
	 * 所有注册的 rebound 回调函数
	 *
	 * @var array[]
	 */
	protected $reboundCallbacks = [];


	/**
	 * The contextual binding map.
	 * 上下文绑定关系数组，这个参数和$buildStack一起，解决递归依赖问题
	 *
	 * @var array[]
	 */
	public $contextual = [];


	/**
	 * The stack of concretions currently being built.
	 *
	 * 这个参数类似于栈，
	 * @var array[]
	 */
	protected $buildStack = [];

	/**
	 * The parameter override stack.
	 *  当前栈的参数
	 * @var array[]
	 */
	protected $with = [];


	/**
	 * The container's method bindings.
	 *  绑定方法数组
	 * @var Closure[]
	 */
	protected $methodBindings = [];




	// ContainerContract 接口定义的方法

	/**
	 * 判断给定的抽象类名|接口名是否被绑定过实现类
	 *
	 * @param  string  $abstract  抽象类名称|接口类
	 * @return bool
	 * @inheritDoc
	 */
	public function bound($abstract)
	{
		return isset($this->bindings[$abstract]) ||
			isset($this->instances[$abstract]) ||
			$this->isAlias($abstract);
	}


	/**
	 * 注册一个绑定关系
	 *
	 * @param  string  $abstract          抽象类
	 * @param  \Closure|string|null  $concrete  闭包|实体类
	 * @param  bool  $shared   是否为单利模式
	 * @return void
	 * @inheritDoc
	 */
	public function bind($abstract, $concrete = null, $shared = false)
	{
		// 旧的删除
		$this->dropStaleInstances($abstract);

		/**
		 * 如果没有给实现类，那么就定义自己为自己的实现类，不太明白这部操作是干什么
		 */
		if (is_null($concrete)) {
			$concrete = $abstract;
		}

		// 进行一个闭包包装
		if (! $concrete instanceof Closure) {
			$concrete = $this->getClosure($abstract, $concrete);
		}

		// 用类变量记录绑定记录
		$this->bindings[$abstract] = compact('concrete', 'shared');

		// 如果曾经解析过，需要处理一写东西
		if ($this->resolved($abstract)) {
			$this->rebound($abstract);
		}
	}

	/**
	 * 绑定单列模式
	 *
	 * @param  string  $abstract
	 * @param  \Closure|string|null  $concrete
	 * @return void
	 *
	 * @inheritDoc
	 */
	public function singleton($abstract, $concrete = null)
	{
		$this->bind($abstract, $concrete, true);
	}

	/**
	 * 将一个对象绑定到容器中
	 *
	 * @param  string  $abstract  抽象类
	 * @param  mixed  $instance   对象
	 * @return mixed
	 *
	 * @inheritDoc
	 */
	public function instance($abstract, $instance)
	{
		// 删除之前有过的别名
		$this->removeAbstractAlias($abstract);

		// 是否绑定过
		$isBound = $this->bound($abstract);

		// 删除别名
		unset($this->aliases[$abstract]);

		// 保存绑定关系
		$this->instances[$abstract] = $instance;

		if ($isBound) {
			$this->rebound($abstract);
		}

		return $instance;
	}

	/**
	 * 刷新机制
	 *
	 * @inheritDoc
	 */
	public function flush()
	{
		$this->aliases = [];
		$this->resolved = [];
		$this->bindings = [];
		$this->instances = [];
		$this->abstractAliases = [];
	}

	/**
	 * 从服务容器中解析所需要的类实例
	 * @param string $abstract
	 * @param array $parameters
	 * @return mixed
	 *
	 * @inheritDoc
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws ReflectionException
	 */
	public function make($abstract, array $parameters = [])
	{
		return $this->resolve($abstract, $parameters);
	}

	/**
	 * Call the given Closure / class@method and inject its dependencies.
	 *  调用闭包或者方法，
	 * @param callable|string $callback
	 * @param array<string, mixed> $parameters
	 * @param string|null $defaultMethod
	 * @return mixed
	 *
	 * @throws ReflectionException
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 */
	public function call($callback, array $parameters = [], $defaultMethod = null)
	{
		return BoundMethod::call($this, $callback, $parameters, $defaultMethod);
	}

	/**
	 * 判断抽象类是否被解析过
	 *
	 * @param  string  $abstract
	 * @return bool
	 * @inheritDoc
	 */
	public function resolved($abstract)
	{
		if ($this->isAlias($abstract)) {
			$abstract = $this->getAlias($abstract);
		}

		return isset($this->resolved[$abstract]) ||
			isset($this->instances[$abstract]);
	}

	/**
	 * @inheritDoc
	 */
	public function resolving($abstract, Closure $callback = null)
	{
		// TODO: Implement resolving() method.
	}

	/**
	 * @inheritDoc
	 */
	public function afterResolving($abstract, Closure $callback = null)
	{
		// TODO: Implement afterResolving() method.
	}



	// ArrayAccess 接口定义的方法

	/**
	 * @inheritDoc
	 */
	public function offsetExists($key)
	{
		return $this->bound($key);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetGet($key)
	{
		return $this->make($key);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet($key, $value)
	{
		$this->bind($key, $value instanceof Closure ? $value : function () use ($value) {
			return $value;
		});
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset($key)
	{
		unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
	}




	// 本类自有的方法

	/**
	 * 判断当前名称是否为一个别名
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function isAlias($name)
	{
		return isset($this->aliases[$name]);
	}

	/**
	 * 删除之前的绑定记录，因为要重新绑定，旧的已经过时了
	 *
	 * @param  string  $abstract
	 * @return void
	 */
	protected function dropStaleInstances($abstract)
	{
		unset($this->instances[$abstract], $this->aliases[$abstract]);
	}

	/**
	 * 包装一个闭包，当需要解析的是再用
	 *
	 * @param  string  $abstract
	 * @param  string  $concrete
	 * @return \Closure
	 */
	protected function getClosure($abstract, $concrete)
	{
		return function ($container, $parameters = []) use ($abstract, $concrete) {
			if ($abstract == $concrete) {
				return $container->build($concrete);
			}

			return $container->resolve(
				$concrete, $parameters, $raiseEvents = false
			);
		};
	}



	/**
	 * 执行绑定到抽象类$abstract上的绑定回调函数
	 *
	 * @param  string  $abstract
	 * @return void|int
	 */
	protected function rebound($abstract)
	{
		return 0; //  待定
		$instance = $this->make($abstract);

		foreach ($this->getReboundCallbacks($abstract) as $callback) {
			call_user_func($callback, $this, $instance);
		}
	}

	/**
	 * 获取注册的绑定回调函数
	 *
	 * @param  string  $abstract
	 * @return array
	 */
	protected function getReboundCallbacks($abstract)
	{
		return $this->reboundCallbacks[$abstract] ?? [];
	}



	/**
	 * 删除别名
	 * @param  string  $searched
	 * @return void
	 */
	protected function removeAbstractAlias($searched)
	{
		if ( ! isset($this->aliases[$searched])) {
			return;
		}

		foreach ($this->abstractAliases as $abstract => $aliases) {
			foreach ($aliases as $index => $alias) {
				if ($alias == $searched) {
					unset($this->abstractAliases[$abstract][$index]);
				}
			}
		}
	}


	/**
	 * 从服务容器中解析类
	 *
	 * @param string $abstract 抽象类|接口类 名字
	 * @param array $parameters 参数
	 * @param bool $raiseEvents 是否调用事件
	 * @return mixed
	 *
	 * @throws BindingResolutionException
	 * @throws ReflectionException
	 */
	protected function resolve($abstract, $parameters = [], $raiseEvents = true)
	{
		// 拿到大名
		$abstract = $this->getAlias($abstract);

		// 获取上下文保存的数据
		$concrete = $this->getContextualConcrete($abstract);

		$needsContextualBuild = ! empty($parameters) || ! is_null($concrete);

		// 单列问题
		if (isset($this->instances[$abstract]) && ! $needsContextualBuild) {
			return $this->instances[$abstract];
		}

		// 把当前用到的参数进栈
		$this->with[] = $parameters;

		if (is_null($concrete)) {
			$concrete = $this->getConcrete($abstract);
		}

		// 如果 concrete 是一个闭包或者实例化的类，直接调用 build 直接解析，否则接着调用 make，进栈
		if ($this->isBuildable($concrete, $abstract)) {
			$object = $this->build($concrete);
		} else {
			$object = $this->make($concrete);
		}

		// 扩展部分
		foreach ($this->getExtenders($abstract) as $extender) {
			$object = $extender($object, $this);
		}

		// 单例模式
		if ($this->isShared($abstract) && ! $needsContextualBuild) {
			$this->instances[$abstract] = $object;
		}

		if ($raiseEvents) {
			$this->fireResolvingCallbacks($abstract, $object);
		}

		//  设置解析标志，表明此抽象类系统已经解析过了
		$this->resolved[$abstract] = true;

		// 参数，出栈
		array_pop($this->with);

		return $object;
	}


	/**
	 * 获取抽象类的别名，这是一个地柜函数，一定要刨根见底，拿到最后的，身份证上的大名
	 *
	 * @param  string  $abstract
	 * @return string
	 */
	public function getAlias($abstract)
	{
		if (! isset($this->aliases[$abstract])) {
			return $abstract;
		}

		return $this->getAlias($this->aliases[$abstract]);
	}


	/**
	 * Get the contextual concrete binding for the given abstract.
	 *  获取上下文信息，在解析 A 的时候，发现 A 依赖 B ，这时，把所有信息进栈(buildStack)，然后开始解析 B
	 * @param  string  $abstract
	 * @return \Closure|string|null
	 */
	protected function getContextualConcrete($abstract)
	{
		if (! is_null($binding = $this->findInContextualBindings($abstract))) {
			return $binding;
		}

		// 如果当前抽象类名称上没有绑定上下文，那就找找他的别名上是否有上下文
		if (empty($this->abstractAliases[$abstract])) {
			return;
		}

		foreach ($this->abstractAliases[$abstract] as $alias) {
			if (! is_null($binding = $this->findInContextualBindings($alias))) {
				return $binding;
			}
		}
	}

	/**
	 * Find the concrete binding for the given abstract in the contextual binding array.
	 * 在上下文绑定关系数组中寻找具体绑定
	 * @param  string  $abstract
	 * @return \Closure|string|null
	 */
	protected function findInContextualBindings($abstract)
	{
		return $this->contextual[end($this->buildStack)][$abstract] ?? null;
	}


	/**
	 * Get the concrete type for a given abstract.
	 *  获取具体实现的类
	 *
	 * @param  string  $abstract
	 * @return mixed
	 */
	protected function getConcrete($abstract)
	{

		if (isset($this->bindings[$abstract])) {
			return $this->bindings[$abstract]['concrete'];
		}

		return $abstract;
	}

	/**
	 * Determine if the given concrete is buildable.
	 * 判断给定的类是否可以实例化
	 * @param  mixed  $concrete
	 * @param  string  $abstract
	 * @return bool
	 */
	protected function isBuildable($concrete, $abstract)
	{
		return $concrete === $abstract || $concrete instanceof Closure;
	}


	/**
	 * Instantiate a concrete instance of the given type.
	 *  实例化一个类
	 *
	 * @param \Closure|string $concrete
	 * @return mixed
	 *
	 * @throws BindingResolutionException
	 * @throws ReflectionException
	 */
	public function build($concrete)
	{
		// 如果是闭包，直接返回闭包
		if ($concrete instanceof Closure) {
			return $concrete($this, $this->getLastParameterOverride());
		}

		try {
			// 反射机制了
			$reflector = new ReflectionClass($concrete);
		} catch (ReflectionException $e) {
			throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
		}

		// 如果该类无法实例化，抛出异常
		if (! $reflector->isInstantiable()) {
			return $this->notInstantiable($concrete);
		}

		// 正在实例化的类，进栈
		$this->buildStack[] = $concrete;

		// 构造函数
		$constructor = $reflector->getConstructor();

		// 如果没有初始化函数，则表明此类没有依赖，直接进行 new Class ，返回对象就行
		if (is_null($constructor)) {
			// 出栈
			array_pop($this->buildStack);
			return new $concrete;
		}

		// 获取构造函数的所有参数
		$dependencies = $constructor->getParameters();

		// 获取所有的构造函数参数数组，然后一个个进行解析
		try {
			$instances = $this->resolveDependencies($dependencies);
		} catch (BindingResolutionException $e) {
			array_pop($this->buildStack);

			throw $e;
		}

		array_pop($this->buildStack);

		return $reflector->newInstanceArgs($instances);
	}


	/**
	 * Get the extender callbacks for a given type.
	 *  获取抽象类的扩展回调  待定，暂不处理
	 * @param  string  $abstract
	 * @return array
	 */
	protected function getExtenders($abstract)
	{
		return [];
		$abstract = $this->getAlias($abstract);

		return $this->extenders[$abstract] ?? [];
	}


	/**
	 * Fire all of the resolving callbacks.
	 * 解析类是的回调函数。。。。 待定
	 *
	 * @param  string  $abstract
	 * @param  mixed  $object
	 * @return void
	 */
	protected function fireResolvingCallbacks($abstract, $object)
	{
		return;
		$this->fireCallbackArray($object, $this->globalResolvingCallbacks);

		$this->fireCallbackArray(
			$object, $this->getCallbacksForType($abstract, $object, $this->resolvingCallbacks)
		);

		$this->fireAfterResolvingCallbacks($abstract, $object);
	}


	/**
	 * Get the last parameter override.
	 * 获取当前正在解析的类所需要的参数
	 * @return array
	 */
	protected function getLastParameterOverride()
	{
		return count($this->with) ? end($this->with) : [];
	}



	/**
	 * Throw an exception that the concrete is not instantiable.
	 *
	 * @param  string  $concrete
	 * @return void
	 *
	 * @throws BindingResolutionException
	 */
	protected function notInstantiable($concrete)
	{
		if (! empty($this->buildStack)) {
			$previous = implode(', ', $this->buildStack);

			$message = "Target [$concrete] is not instantiable while building [$previous].";
		} else {
			$message = "Target [$concrete] is not instantiable.";
		}

		throw new BindingResolutionException($message);
	}


	/**
	 * Resolve all of the dependencies from the ReflectionParameters.
	 *  解决依赖，这就是 laravel 实现依赖注入的关键
	 * @param array $dependencies
	 * @return array
	 *
	 * @throws BindingResolutionException
	 * @throws ReflectionException
	 */
	protected function resolveDependencies(array $dependencies)
	{
		$results = [];

		foreach ($dependencies as $dependency) {

			if ($this->hasParameterOverride($dependency)) {
				$results[] = $this->getParameterOverride($dependency);

				continue;
			}

			/**
			 * is_null($dependency->getClass()) 如果为真，表示是一个基础类型的数据，
			 */
			$results[] = is_null($dependency->getClass())
				? $this->resolvePrimitive($dependency)
				: $this->resolveClass($dependency);
		}

		return $results;
	}

	/**
	 * Determine if the given dependency has a parameter override.
	 *  从当前的 with 中判断是否有给初始化参数的值
	 * @param  \ReflectionParameter  $dependency
	 * @return bool
	 */
	protected function hasParameterOverride($dependency)
	{
		return array_key_exists(
			$dependency->name, $this->getLastParameterOverride()
		);
	}


	/**
	 * Get a parameter override for a dependency.
	 *  从当前的 with 中获取初始化参数的值
	 * @param  \ReflectionParameter  $dependency
	 * @return mixed
	 */
	protected function getParameterOverride($dependency)
	{
		return $this->getLastParameterOverride()[$dependency->name];
	}


	/**
	 * Resolve a non-class hinted primitive dependency.
	 *  解析一个基础类型是参数  int|string|boolean
	 * @param \ReflectionParameter $parameter
	 * @return mixed
	 *
	 * @throws ReflectionException
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 */
	protected function resolvePrimitive(ReflectionParameter $parameter)
	{
		if (! is_null($concrete = $this->getContextualConcrete('$'.$parameter->name))) {
			return $concrete instanceof Closure ? $concrete($this) : $concrete;
		}
		// 默认值
		if ($parameter->isDefaultValueAvailable()) {
			return $parameter->getDefaultValue();
		}

		$this->unresolvablePrimitive($parameter);
	}


	/**
	 * Throw an exception for an unresolvable primitive.
	 *  当依赖没办法解析时，抛出异常
	 *
	 * @param  \ReflectionParameter  $parameter
	 * @return void
	 *
	 * @throws BindingResolutionException
	 */
	protected function unresolvablePrimitive(ReflectionParameter $parameter)
	{
		$message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

		throw new BindingResolutionException($message);
	}


	/**
	 * Resolve a class based dependency from the container.
	 *  依赖参数中有其他类，直接调用 make 进行解析
	 * @param \ReflectionParameter $parameter
	 * @return mixed
	 *
	 * @throws BindingResolutionException
	 * @throws ReflectionException
	 */
	protected function resolveClass(ReflectionParameter $parameter)
	{
		try {
			return $this->make($parameter->getClass()->name);
		}
		catch (BindingResolutionException $e) {
			// 无法解析的话，就找默认值
			if ($parameter->isDefaultValueAvailable()) {
				return $parameter->getDefaultValue();
			}

			throw $e;
		}
	}


	/**
	 * Determine if a given type is shared.
	 *  判断是否为单列
	 * @param  string  $abstract
	 * @return bool
	 */
	public function isShared($abstract)
	{
		return isset($this->instances[$abstract]) ||
			(isset($this->bindings[$abstract]['shared']) &&
				$this->bindings[$abstract]['shared'] === true);
	}


	/**
	 * Set the shared instance of the container.
	 *  注册 application 自己的实例关系
	 * @param  ContainerContract|null  $container
	 * @return ContainerContract|static
	 */
	public static function setInstance(ContainerContract $container = null)
	{
		return static::$instance = $container;
	}

	/**
	 * Get the globally available instance of the container.
	 *
	 * @return static
	 */
	public static function getInstance()
	{
		if (is_null(static::$instance)) {
			static::$instance = new static;
		}

		return static::$instance;
	}



	/**
	 * Alias a type to a different name.
	 *  设置别名
	 * @param  string  $abstract
	 * @param  string  $alias
	 * @return void
	 *
	 * @throws \LogicException
	 */
	public function alias($abstract, $alias)
	{
		if ($alias === $abstract) {
			throw new LogicException("[{$abstract}] is aliased to itself.");
		}

		$this->aliases[$alias] = $abstract;

		$this->abstractAliases[$abstract][] = $alias;
	}


	/**
	 * Dynamically access container services.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this[$key];
	}

	/**
	 * Dynamically set container services.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this[$key] = $value;
	}



	/**
	 * Determine if the container has a method binding.
	 *  判断容器上是否绑定了方法
	 * @param  string  $method
	 * @return bool
	 */
	public function hasMethodBinding($method)
	{
		return isset($this->methodBindings[$method]);
	}


	/**
	 * Bind a callback to resolve with Container::call.
	 *  绑定方法
	 * @param  array|string  $method
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function bindMethod($method, $callback)
	{
		$this->methodBindings[$this->parseBindMethod($method)] = $callback;
	}

	/**
	 * Get the method to be bound in class@method format.
	 *  处理格式
	 * @param  array|string  $method
	 * @return string
	 */
	protected function parseBindMethod($method)
	{
		if (is_array($method)) {
			return $method[0].'@'.$method[1];
		}

		return $method;
	}

	/**
	 * Get the method binding for the given method.
	 *  调用绑定方法
	 * @param  string  $method
	 * @param  mixed  $instance
	 * @return mixed
	 */
	public function callMethodBinding($method, $instance)
	{
		return call_user_func($this->methodBindings[$method], $instance, $this);
	}

}
