<?php


namespace Lufeijun1234\Abstracts\Serviceprovider;


use Lufeijun1234\Routing\UrlGenerator;
use Lufeijun1234\Traits\ForwardsCalls;

class RouteServiceProvider extends ServiceProvider
{
	use ForwardsCalls;

	/**
	 * The controller namespace for the application.
	 *
	 * @var string|null
	 */
	protected $namespace;


	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->setRootControllerNamespace();

		// 路由文件缓存，暂时不考虑 待定
		if ($this->routesAreCached()) {
			$this->loadCachedRoutes();
		} else {
			$this->loadRoutes();

			$this->app->booted(function () {
				$this->app['router']->getRoutes()->refreshNameLookups();
				$this->app['router']->getRoutes()->refreshActionLookups();
			});
		}
	}


	// 设置控制器命名空间，待定
	protected function setRootControllerNamespace()
	{
		if (! is_null($this->namespace)) {
			$this->app[UrlGenerator::class]->setRootControllerNamespace($this->namespace);
		}
	}


	/**
	 * Determine if the application routes are cached.
	 *  是否有路由缓存
	 * @return bool
	 */
	protected function routesAreCached()
	{
		return 0 && $this->app->routesAreCached();
	}


	/**
	 * Load the application routes.
	 *  加载系统路由
	 * @return void
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws \ReflectionException
	 */
	protected function loadRoutes()
	{
		if (method_exists($this, 'map')) {
			$this->app->call([$this, 'map']);
		}
	}

}
