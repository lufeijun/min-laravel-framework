<?php


namespace Lufeijun1234\Log;


use Lufeijun1234\Abstracts\Serviceprovider\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{

	/**
	 * Register the service provider.
	 *  注册服务提供者
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('log', function ($app) {
			return new LogManager($app);
		});
	}
}
