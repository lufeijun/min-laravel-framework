<?php
namespace Lufeijun1234\Foundation\Bootstrap;

use ErrorException;
use Exception;
use Lufeijun1234\Contracts\Debug\ExceptionHandler;
use Lufeijun1234\Foundation\Application;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Throwable;


class HandleExceptions
{
	/**
	 * Reserved memory so that errors can be displayed properly on memory exhaustion.
	 *  说是为了保留内存
	 * @var string
	 */
	public static $reservedMemory;

	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	protected $app;


	/**
	 * Bootstrap the given application.
	 *
	 * @param  Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		self::$reservedMemory = str_repeat('x', 10240);

		$this->app = $app;

		// 错误等级
		error_reporting(-1);

		// 错误处理函数
		set_error_handler([$this, 'handleError']);

		// 异常处理函数
		set_exception_handler([$this, 'handleException']);

		// 异常结束时执行的函数
		register_shutdown_function([$this, 'handleShutdown']);

		if (! $app->environment('testing')) {
			ini_set('display_errors', 'Off');
		}
	}



	/**
	 * Convert PHP errors to ErrorException instances.
	 *  转换
	 * @param  int  $level
	 * @param  string  $message
	 * @param  string  $file
	 * @param  int  $line
	 * @param  array  $context
	 * @return void
	 *
	 * @throws \ErrorException
	 */
	public function handleError($level, $message, $file = '', $line = 0, $context = [])
	{
		if (error_reporting() & $level) {
			throw new ErrorException($message, 0, $level, $file, $line);
		}
	}

	public function handleException(Throwable $e)
	{
		try {
			// 提前占好位的内存
			self::$reservedMemory = null;

			$this->getExceptionHandler()->report($e);
		} catch (Exception $e) {
			//
		}

		// 暂时先不管命令行
		if ( 0 && $this->app->runningInConsole()) {
			$this->renderForConsole($e);
		} else {
			$this->renderHttpResponse($e);
		}
	}


	public function handleShutdown()
	{
		if (! is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
			$this->handleException($this->fatalErrorFromPhpError($error, 0));
		}
	}


	/**
	 * Determine if the error type is fatal.
	 *
	 * @param  int  $type
	 * @return bool
	 */
	protected function isFatal($type)
	{
		return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
	}

	/**
	 * Create a new fatal error instance from an error array.
	 *
	 * @param  array  $error
	 * @param  int|null  $traceOffset
	 * @return \Symfony\Component\ErrorHandler\Error\FatalError
	 */
	protected function fatalErrorFromPhpError(array $error, $traceOffset = null)
	{
		return new FatalError($error['message'], 0, $error, $traceOffset);
	}


	/**
	 * Render an exception as an HTTP response and send it.
	 *  渲染好响应
	 * @param \Throwable $e
	 * @return void
	 * @throws Throwable
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws \ReflectionException
	 */
	protected function renderHttpResponse(Throwable $e)
	{
		$this->getExceptionHandler()->render($this->app['request'], $e)->send();
	}



	/**
	 * Get an instance of the exception handler.
	 *
	 * @return ExceptionHandler
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws \ReflectionException
	 */
	protected function getExceptionHandler()
	{
		return $this->app->make(ExceptionHandler::class);
	}

}
