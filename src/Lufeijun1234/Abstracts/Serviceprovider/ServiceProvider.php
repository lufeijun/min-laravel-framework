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

}
