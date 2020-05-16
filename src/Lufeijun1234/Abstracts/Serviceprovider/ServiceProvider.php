<?php
namespace Lufeijun1234\Abstracts\Serviceprovider;

use Lufeijun1234\Foundation\Application;

abstract class ServiceProvider
{
	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	protected $app;


	/**
	 * Create a new service provider instance.
	 *  初始化函数
	 *
	 * @param Application  $app
	 * @return void
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}


	/**
	 * Register any application services.
	 *  注册程序
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}

	/**
	 * Get the events that trigger this service provider to register.
	 *
	 * @return array
	 */
	public function when()
	{
		return [];
	}

	/**
	 * Determine if the provider is deferred.
	 *
	 * @return bool
	 */
	public function isDeferred()
	{
		return $this instanceof DeferrableProvider;
	}

}
