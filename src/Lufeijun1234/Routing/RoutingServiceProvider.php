<?php


namespace Lufeijun1234\Routing;


use Lufeijun1234\Abstracts\Serviceprovider\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{


	public function register()
	{
		$this->registerRouter();
	}

	/**
	 * Register the router instance.
	 *  综合处理类，类似于总入口，处理路由相关类
	 * @return void
	 */
	protected function registerRouter()
	{
		$this->app->singleton('router', function ($app) {
			return new Router($app['events'], $app);
		});
	}

}
