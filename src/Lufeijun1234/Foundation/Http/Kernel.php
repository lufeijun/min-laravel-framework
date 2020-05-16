<?php
namespace Lufeijun1234\Foundation\Http;


use Lufeijun1234\Contracts\Http\KernelContract;
use Lufeijun1234\Foundation\Application;

class Kernel implements KernelContract
{

	/**
	 * The application implementation.
	 *
	 * @var Lufeijun1234\Foundation\Application
	 */
	protected $app;

	/**
	 * The router instance.
	 *  路由类
	 * @var \Illuminate\Routing\Router
	 */
	protected $router;


	/**
	 * The bootstrap classes for the application.
	 *   启动的程序类
	 * @var array
	 */
	protected $bootstrappers = [
		\Lufeijun1234\Foundation\Bootstrap\LoadEnvironmentVariables::class,
		\Lufeijun1234\Foundation\Bootstrap\LoadConfiguration::class,
//		\Illuminate\Foundation\Bootstrap\HandleExceptions::class,
		\Lufeijun1234\Foundation\Bootstrap\RegisterFacades::class,
		\Lufeijun1234\Foundation\Bootstrap\RegisterProviders::class,
		\Lufeijun1234\Foundation\Bootstrap\BootProviders::class,
	];


	/**
	 * Create a new HTTP kernel instance.
	 *
	 * @param  \Lufeijun1234\Foundation\Application  $app
	 * @ param  \Illuminate\Routing\Router  $router , Router $router
	 * @return void
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;

		// 路由，暂时不管
		// $this->router = $router;

		// 中间件
		// $this->syncMiddlewareToRouter();
	}


	/**
	 * @inheritDoc
	 */
	public function bootstrap()
	{
		// TODO: Implement bootstrap() method.
		if (! $this->app->hasBeenBootstrapped()) {
			$this->app->bootstrapWith($this->bootstrappers());
		}
	}

	public function bootstrappers()
	{
		return $this->bootstrappers;
	}

	/**
	 * @inheritDoc
	 */
	public function handle( $request)
	{
		try {
			$request->enableHttpMethodParameterOverride();

			$response = $this->sendRequestThroughRouter($request);
		} catch (Throwable $e) {
			$this->reportException($e);
			$response = $this->renderException($request, $e);
		}

		// 事件部分，待定
//		$this->app['events']->dispatch(
//			new RequestHandled($request, $response)
//		);

		return $response;
	}


	/**
	 * Send the given request through the middleware / router.
	 *
	 * @param  \Lufeijun1234\Http\Request  $request
	 * @return \Lufeijun1234\Http\Response
	 */
	protected function sendRequestThroughRouter($request)
	{
		$this->app->instance('request', $request);

		//Facade::clearResolvedInstance('request');

		$this->bootstrap();

//		return (new Pipeline($this->app))
//			->send($request)
//			->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
//			->then($this->dispatchToRouter());
	}



	/**
	 * @inheritDoc
	 */
	public function terminate($request, $response)
	{
		// TODO: Implement terminate() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getApplication()
	{
		// TODO: Implement getApplication() method.
	}
}
