<?php
namespace Lufeijun1234\Foundation;


use Closure;
use Lufeijun1234\Abstracts\Serviceprovider\ServiceProvider;
use Lufeijun1234\Container\Container;
use Lufeijun1234\Contracts\Container\ContainerContract;
use Lufeijun1234\Events\EventServiceProvider;
use Lufeijun1234\Filesystem\Filesystem;
use Lufeijun1234\Log\LogServiceProvider;
use Lufeijun1234\Routing\RoutingServiceProvider;
use Lufeijun1234\Support\Arr;
use Lufeijun1234\Support\Env;
use Lufeijun1234\Support\Str;

class Application extends Container
{
	/**
	 * 系统的框架版本
	 *
	 * @var string
	 */
	const VERSION = '0.0.1';

	/**
	 * 框架的根目录
	 *
	 * @var string
	 */
	protected $basePath;

	/**
	 * The custom application path defined by the developer.
	 *
	 * @var string
	 */
	protected $appPath;

	/**
	 * The custom storage path defined by the developer.
	 *
	 * @var string
	 */
	protected $storagePath;



	/**
	 * All of the registered service providers.
	 *  所有注册在籍的服务提供者
	 * @var Abstracts\Serviceprovider\ServiceProvider[]
	 */
	protected $serviceProviders = [];

	/**
	 * The names of the loaded service providers.
	 *  key 是服务提供者类名
	 * @var array
	 */
	protected $loadedProviders = [];


	/**
	 * Indicates if the application has "booted".
	 *   系统核心组件启动为 boot ？
	 * @var bool
	 */
	protected $booted = false;


	/**
	 * Indicates if the application has been bootstrapped before.
	 *  http 相关服务启动为 bootstrap ？
	 * @var bool
	 */
	protected $hasBeenBootstrapped = false;


	/**
	 * The custom environment path defined by the developer.
	 *  自定义的 env 配置路径
	 * @var string
	 */
	protected $environmentPath;


	/**
	 * The environment file to load during bootstrapping.
	 *  配置文件
	 * @var string
	 */
	protected $environmentFile = '.env';


	/**
	 * The prefixes of absolute cache paths for use during normalization.
	 *
	 * @var array
	 */
	protected $absoluteCachePathPrefixes = [DIRECTORY_SEPARATOR];


	/**
	 * The deferred services and their providers.
	 *
	 * @var array
	 */
	protected $deferredServices = [];


	/**
	 * The array of booting callbacks.
	 *
	 * @var callable[]
	 */
	protected $bootingCallbacks = [];


	/**
	 * The array of booted callbacks.
	 *
	 * @var callable[]
	 */
	protected $bootedCallbacks = [];


  /**
   * The application namespace.
   * 程序命名空间
   * @var string
   */
  protected $namespace;



	/**
	 * Application constructor.
	 * @param null $basePath 项目根目录
	 */
	public function __construct($basePath = null)
	{
		if ($basePath) {
			$this->setBasePath($basePath);
		}

		$this->registerBaseBindings();
		$this->registerBaseServiceProviders();
		$this->registerCoreContainerAliases();
	}


  /**
   * Get the version number of the application.
   *
   * @return string
   */
  public function version()
  {
    return static::VERSION;
  }

	/**
	 * Set the base path for the application.
	 *
	 * @param  string  $basePath
	 * @return $this
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = rtrim($basePath, '\/');

		$this->bindPathsInContainer();

		return $this;
	}

	/**
	 * 为应用绑定一系列文件路径
	 *
	 * @return void
	 */
	protected function bindPathsInContainer()
	{
		$this->instance('path', $this->path());
		$this->instance('path.base', $this->basePath());
//		$this->instance('path.lang', $this->langPath());
		$this->instance('path.config', $this->configPath());
		$this->instance('path.public', $this->publicPath());
		$this->instance('path.storage', $this->storagePath());
//		$this->instance('path.database', $this->databasePath());
//		$this->instance('path.resources', $this->resourcePath());
		$this->instance('path.bootstrap', $this->bootstrapPath());
	}




	/**
	 * Get the path to the application "app" directory.
	 *  设置 app 路径
	 * @param  string  $path
	 * @return string
	 */
	public function path($path = '')
	{
		$appPath = $this->appPath ?: $this->basePath.DIRECTORY_SEPARATOR.'app';

		return $appPath.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the base path of the Laravel installation.
	 *  设置根路径
	 * @param  string  $path Optionally, a path to append to the base path
	 * @return string
	 */
	public function basePath($path = '')
	{
		return $this->basePath.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the path to the bootstrap directory.
	 *  设置 bootstrap 路径
	 * @param  string  $path Optionally, a path to append to the bootstrap path
	 * @return string
	 */
	public function bootstrapPath($path = '')
	{
		return $this->basePath.DIRECTORY_SEPARATOR.'bootstrap'.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}


	/**
	 * Get the path to the public / web directory.
	 *   设置 web 可以访问到的路径
	 * @return string
	 */
	public function publicPath()
	{
		return $this->basePath.DIRECTORY_SEPARATOR.'public';
	}


	/**
	 * Get the path to the storage directory.
	 *
	 * @return string
	 */
	public function storagePath()
	{
		return $this->storagePath ?: $this->basePath.DIRECTORY_SEPARATOR.'storage';
	}


	/**
	 * Get the path to the application configuration files.
	 *
	 * @param  string  $path Optionally, a path to append to the config path
	 * @return string
	 */
	public function configPath($path = '')
	{
		return $this->basePath.DIRECTORY_SEPARATOR.'config'.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}


	/**
	 * Get the path to the environment file directory.
	 *  环境变量配置路径
	 * @return string
	 */
	public function environmentPath()
	{
		return $this->environmentPath ?: $this->basePath;
	}

	/**
	 * Get the environment file the application is using.
	 *
	 * @return string
	 */
	public function environmentFile()
	{
		return $this->environmentFile ?: '.env';
	}


	/**
	 * Get or check the current application environment.
	 *
	 * @param  string|array  $environments
	 * @return string|bool
	 */
	public function environment(...$environments)
	{
		if (count($environments) > 0) {
			$patterns = is_array($environments[0]) ? $environments[0] : $environments;

			return Str::is($patterns, $this['env']);
		}

		return $this['env'];
	}

	// 判断系统当前环境
	public function detectEnvironment(Closure $callback)
	{
		$args = $_SERVER['argv'] ?? null;

		return $this['env'] = (new EnvironmentDetector)->detect($callback, $args);
	}

	public function isLocal()
	{
		return $this['env'] === 'local';
	}

	public function isProduction()
	{
		return $this['env'] === 'production';
	}



	/**
	 * Register the basic bindings into the container.
	 *  注册一些基础的服务类绑定
	 * @return void
	 */
	protected function registerBaseBindings()
	{
		// 绑定 application 自己
		static::setInstance($this);

		// 设置 app ---> application 的对应关系
		$this->instance('app', $this);
		$this->instance(Container::class, $this);

		// 这个暂时不管
		// $this->singleton(Mix::class);

		// 这个是包自动发现的实现代码，暂时不管
		// $this->singleton(PackageManifest::class, function () {
		//	return new PackageManifest(
		//		new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
		//	);
		// });
	}



	/**
	 * Register all of the base service providers.
	 *  设置基础服务提供者
	 * @return void
	 */
	protected function registerBaseServiceProviders()
	{
		$this->register(new EventServiceProvider($this));
		$this->register(new RoutingServiceProvider($this));
		$this->register(new LogServiceProvider($this));
	}


	/**
	 * Register the core class aliases in the container.
	 *  注册核心类别名
	 * @return void
	 */
	public function registerCoreContainerAliases()
	{
		$all = [
			'app'       => [self::class, Container::class, ContainerContract::class],
			'router'    => [\Lufeijun1234\Routing\Router::class],
			'request'   => [\Lufeijun1234\Http\Request::class, \Symfony\Component\HttpFoundation\Request::class],
			'log'       => [Lufeijun1234\Log\LogManager::class, \Psr\Log\LoggerInterface::class],


		];

		foreach ($all as $key => $aliases) {
			foreach ($aliases as $alias) {
				$this->alias($key, $alias);
			}
		}
	}

	/**
	 * Flush the container of all bindings and resolved instances.
	 *
	 * @return void
	 */
	public function flush()
	{
		parent::flush();

		$this->buildStack = [];
		$this->loadedProviders = [];
		$this->bootedCallbacks = [];
		$this->bootingCallbacks = [];
		$this->deferredServices = [];
		$this->reboundCallbacks = [];
		$this->serviceProviders = [];
		//$this->resolvingCallbacks = [];
		//$this->terminatingCallbacks = [];
		//$this->afterResolvingCallbacks = [];
		//$this->globalResolvingCallbacks = [];
	}


	// 服务提供者

	/**
	 * Register a service provider with the application.
	 *  往应用程序中注册一个服务提供者
	 *
	 * @param  ServiceProvider|string  $provider
	 * @param  bool  $force
	 * @return ServiceProvider
	 */
	public function register($provider, $force = false)
	{
		// 已经注册过，并且没有需要重新注册
		if ( ($registered = $this->getProvider($provider)) && ! $force) {
			return $registered;
		}

		// 如果参数是字符串，
		if (is_string($provider)) {
			$provider = $this->resolveProvider($provider);
		}

		// 调用服务提供者的 register 方法
		$provider->register();

		// 根据属性设置，在服务容器中进行绑定一下实现关系
		if (property_exists($provider, 'bindings')) {
			foreach ($provider->bindings as $key => $value) {
				$this->bind($key, $value);
			}
		}
		if (property_exists($provider, 'singletons')) {
			foreach ($provider->singletons as $key => $value) {
				$this->singleton($key, $value);
			}
		}

		$this->markAsRegistered($provider);

		// 如果系统启动了，就需要调用服务提供者的 boot 方法，
		if ($this->isBooted()) {
			$this->bootProvider($provider);
		}

		return $provider;
	}


	/**
	 * Get the registered service provider instance if it exists.
	 *
	 * @param  ServiceProvider|string  $provider
	 * @return ServiceProvider|null
	 */
	public function getProvider($provider)
	{
		return array_values($this->getProviders($provider))[0] ?? null;
	}

	/**
	 * Get the registered service provider instances if any exist.
	 *  获取服务提供者
	 * @param  ServiceProvider|string  $provider
	 * @return array
	 */
	public function getProviders($provider)
	{
		$name = is_string($provider) ? $provider : get_class($provider);

		return Arr::where($this->serviceProviders, function ($value) use ($name) {
			return $value instanceof $name;
		});
	}

	/**
	 * Resolve a service provider instance from the class name.
	 *  通过类名直接 new 一个服务提供者类
	 * @param  string  $provider
	 * @return ServiceProvider
	 */
	public function resolveProvider($provider)
	{
		return new $provider($this);
	}

	/**
	 * Mark the given provider as registered.
	 *  标记一下注册过的服务提供者
	 * @param  ServiceProvider  $provider
	 * @return void
	 */
	protected function markAsRegistered($provider)
	{
		$this->serviceProviders[] = $provider;

		$this->loadedProviders[get_class($provider)] = true;
	}

  // 做个样子先
  public function loadDeferredProviders()
  {
    return;
    // We will simply spin through each of the deferred providers and register each
    // one and boot them if the application has booted. This should make each of
    // the remaining services available to this application for immediate use.
    foreach ($this->deferredServices as $service => $provider) {
      $this->loadDeferredProvider($service);
    }

  }

    /**
	 * Boot the given service provider.
	 *  调用服务提供者的 boot 方法
	 * @param ServiceProvider $provider
	 * @return mixed
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws \ReflectionException
	 */
	protected function bootProvider(ServiceProvider $provider)
	{
		if (method_exists($provider, 'boot')) {
			return $this->call([$provider, 'boot']);
		}
	}



	/**
	 * Determine if the application has been bootstrapped before.
	 *  判断 application 是否被 bootstrap 过
	 * @return bool
	 */
	public function hasBeenBootstrapped()
	{
		return $this->hasBeenBootstrapped;
	}

	/**
	 * Run the given array of bootstrap classes.
	 *  启动一些服务
	 * @param array $bootstrappers
	 * @return void
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws \ReflectionException
	 */
	public function bootstrapWith(array $bootstrappers)
	{
		$this->hasBeenBootstrapped = true;

		foreach ($bootstrappers as $bootstrapper) {
			$this['events']->dispatch('bootstrapping: '.$bootstrapper, [$this]);

			$this->make($bootstrapper)->bootstrap($this);

			$this['events']->dispatch('bootstrapped: '.$bootstrapper, [$this]);
		}
	}


	/**
	 * Register all of the configured providers.
	 *  注册服务提供者
	 * @return void
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws \ReflectionException
	 * @throws \Lufeijun1234\Filesystem\FileNotFoundException
	 */
	public function registerConfiguredProviders()
	{
		$providers = $this['config']->get('app.providers');

		(new ProviderRepository($this, new Filesystem, $this->getCachedServicesPath()))
			->load($providers);
	}



	/**
	 * Get the path to the cached services.php file.
	 *
	 * @return string
	 */
	public function getCachedServicesPath()
	{
		return $this->normalizeCachePath('APP_SERVICES_CACHE', 'cache/services.php');
	}



	/**
	 * Normalize a relative or absolute path to a cache file.
	 *
	 * @param  string  $key
	 * @param  string  $default
	 * @return string
	 */
	protected function normalizeCachePath($key, $default)
	{
		if (is_null($env = Env::get($key))) {
			return $this->bootstrapPath($default);
		}

		return Str::startsWith($env, $this->absoluteCachePathPrefixes)
			? $env
			: $this->basePath($env);
	}



	/**
	 * Add an array of services to the application's deferred services.
	 *  延迟加载
	 * @param  array  $services
	 * @return void
	 */
	public function addDeferredServices(array $services)
	{
		$this->deferredServices = array_merge($this->deferredServices, $services);
	}

	/**
	 * Get the application's deferred services.
	 *
	 * @return array
	 */
	public function getDeferredServices()
	{
		return $this->deferredServices;
	}

	/**
	 * Set the application's deferred services.
	 *
	 * @param  array  $services
	 * @return void
	 */
	public function setDeferredServices(array $services)
	{
		$this->deferredServices = $services;
	}

	/**
	 * Determine if the given service is a deferred service.
	 *
	 * @param  string  $service
	 * @return bool
	 */
	public function isDeferredService($service)
	{
		return isset($this->deferredServices[$service]);
	}




	// 启动 boot
	/**
	 * Determine if the application has booted.
	 *
	 * @return bool
	 */
	public function isBooted()
	{
		return $this->booted;
	}

	/**
	 * Boot the application's service providers.
	 *
	 * @return void
	 */
	public function boot()
	{
		if ($this->isBooted()) {
			return;
		}


		// 调用注册的回调函数
		$this->fireAppCallbacks($this->bootingCallbacks);

		array_walk($this->serviceProviders, function ($p) {
			$this->bootProvider($p);
		});

		$this->booted = true;

		$this->fireAppCallbacks($this->bootedCallbacks);
	}


	/**
	 * Call the booting callbacks for the application.
	 *  调用回调函数
	 * @param  callable[]  $callbacks
	 * @return void
	 */
	protected function fireAppCallbacks(array $callbacks)
	{
		foreach ($callbacks as $callback) {
			$callback($this);
		}
	}

	/**
	 * Register a new "booted" listener.
	 *
	 * @param  callable  $callback
	 * @return void
	 */
	public function booted($callback)
	{
		$this->bootedCallbacks[] = $callback;

		if ($this->isBooted()) {
			$this->fireAppCallbacks([$callback]);
		}
	}


  /**
   * Get the application namespace.
   *
   * @return string
   *
   * @throws \RuntimeException
   */
  public function getNamespace()
  {
    if (! is_null($this->namespace)) {
      return $this->namespace;
    }

    $composer = json_decode(file_get_contents($this->basePath('composer.json')), true);



    foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
      foreach ((array) $path as $pathChoice) {
        if (realpath($this->path()) === realpath($this->basePath($pathChoice))) {
          return $this->namespace = $namespace;
        }
      }
    }

    throw new \RuntimeException('Unable to detect application namespace.');
  }



}
