<?php
namespace Lufeijun1234\Foundation;


use Lufeijun1234\Container\Container;
use Lufeijun1234\Contracts\Container\ContainerContract;

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
//		$this->instance('path.config', $this->configPath());
		$this->instance('path.public', $this->publicPath());
//		$this->instance('path.storage', $this->storagePath());
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
		return;
		$this->register(new EventServiceProvider($this));
		$this->register(new LogServiceProvider($this));
		$this->register(new RoutingServiceProvider($this));
	}


	/**
	 * Register the core class aliases in the container.
	 *  注册核心类别名
	 * @return void
	 */
	public function registerCoreContainerAliases()
	{
		$all = [
			'app'   => [self::class, Container::class, ContainerContract::class],

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
		//$this->loadedProviders = [];
		//$this->bootedCallbacks = [];
		//$this->bootingCallbacks = [];
		//$this->deferredServices = [];
		$this->reboundCallbacks = [];
		//$this->serviceProviders = [];
		//$this->resolvingCallbacks = [];
		//$this->terminatingCallbacks = [];
		//$this->afterResolvingCallbacks = [];
		//$this->globalResolvingCallbacks = [];
	}

}
