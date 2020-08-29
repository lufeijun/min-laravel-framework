<?php


namespace Lufeijun1234\Foundation\Exceptions;

use Exception;
use HttpResponseException;
use Lufeijun1234\Container\Container;
use Lufeijun1234\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Lufeijun1234\Contracts\Support\Responsable;
use Lufeijun1234\Http\JsonResponse;
use Lufeijun1234\Http\Response;
use Lufeijun1234\Routing\Router;
use Psr\Log\LoggerInterface;


use Lufeijun1234\Support\Arr;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Throwable;


class Handler implements ExceptionHandlerContract
{
	/**
	 * The container implementation.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * A list of the exception types that are not reported.
	 *
	 * @var array
	 */
	protected $dontReport = [];

	/**
	 * A list of the internal exception types that should not be reported.
	 * 内部异常列表
	 * @var array
	 */
	protected $internalDontReport = [

	];

	/**
	 * A list of the inputs that are never flashed for validation exceptions.
	 *
	 * @var array
	 */
	protected $dontFlash = [
		'password',
		'password_confirmation',
	];

	/**
	 * Create a new exception handler instance.
	 *
	 * @param Container  $container
	 * @return void
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}



	// 接口方法
	/**
	 * @inheritDoc
	 */
	public function report(Throwable $e)
	{
		// TODO: Implement report() method.

		// 判断是否需要上报
		if ($this->shouldntReport($e)) {
			return;
		}

		// 自己有上报函数
		if (is_callable($reportCallable = [$e, 'report'])) {
			$this->container->call($reportCallable);

			return;
		}

		try {
			$logger = $this->container->make(LoggerInterface::class);
		} catch (Exception $loggerException) {
			throw $e;
		}


		$logger->error(
			$e->getMessage(),
			array_merge(
				$this->exceptionContext($e),
				$this->context(),
				['exception' => $e]
			)
		);
	}

	/**
	 * Get the default context variables for logging.
	 *
	 * @return array
	 */
	protected function context()
	{
		try {
			return array_filter([
				'exception' => '异常',
				// 'userId' => Auth::id(),
				// 'email' => optional(Auth::user())->email,
			]);
		} catch (Throwable $e) {
			return [];
		}
	}


	/**
	 * Get the default exception context variables for logging.
	 *
	 * @param  \Throwable  $e
	 * @return array
	 */
	protected function exceptionContext(Throwable $e)
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function shouldReport(Throwable $e)
	{
		// TODO: Implement shouldReport() method.
		return ! $this->shouldntReport($e);
	}

	protected function shouldntReport(Throwable $e)
	{
		$dontReport = array_merge($this->dontReport, $this->internalDontReport);

		return ! is_null(Arr::first($dontReport, function ($type) use ($e) {
			return $e instanceof $type;
		}));
	}

	/**
	 * laravel 系统提供的影响函数
	 * @inheritDoc
	 */
	public function render($request, Throwable $e)
	{
		// TODO: Implement render() method.
		if (method_exists($e, 'render') && $response = $e->render($request)) {
			return Router::toResponse($request, $response);
		} elseif ($e instanceof Responsable) {
			return $e->toResponse($request);
		}

		$e = $this->prepareException($e);

		if ($e instanceof HttpResponseException) {
			return $e->getResponse();
		} elseif (0 && $e instanceof AuthenticationException) {
			return $this->unauthenticated($request, $e);
		} elseif (0 && $e instanceof ValidationException) {
			return $this->convertValidationExceptionToResponse($e, $request);
		}



		return $request->expectsJson()
			? $this->prepareJsonResponse($request, $e)
			: $this->prepareResponse($request, $e);

	}

	/**
	 * Prepare exception for rendering.
	 *
	 * @param  \Throwable  $e
	 * @return \Throwable
	 */
	protected function prepareException(Throwable $e)
	{
		if ( 0 && $e instanceof ModelNotFoundException) {
			$e = new NotFoundHttpException($e->getMessage(), $e);
		} elseif ( 0 && $e instanceof AuthorizationException) {
			$e = new AccessDeniedHttpException($e->getMessage(), $e);
		} elseif (0 && $e instanceof TokenMismatchException) {
			$e = new HttpException(419, $e->getMessage(), $e);
		} elseif ($e instanceof SuspiciousOperationException) {
			$e = new NotFoundHttpException('Bad hostname provided.', $e);
		}

		return $e;
	}

	/**
	 * Prepare a JSON response for the given exception.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Throwable  $e
	 * @return JsonResponse
	 */
	protected function prepareJsonResponse($request, Throwable $e)
	{
		return new JsonResponse(
			$this->convertExceptionToArray($e),
			$this->isHttpException($e) ? $e->getStatusCode() : 500,
			$this->isHttpException($e) ? $e->getHeaders() : [],
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
	}

	protected function convertExceptionToArray(Throwable $e)
	{
		return config('app.debug') ? [
			'message' => $e->getMessage(),
			'exception' => get_class($e),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
//			'trace' => collect($e->getTrace())->map(function ($trace) {
//				return Arr::except($trace, ['args']);
//			})->all(),
			'trace' => $e->getTrace(),
		] : [
			'message' => $this->isHttpException($e) ? $e->getMessage() : 'Server Error',
		];
	}


	/**
	 * Prepare a response for the given exception.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Throwable  $e
	 * @return SymfonyResponse
	 */
	protected function prepareResponse($request, Throwable $e)
	{
		if (! $this->isHttpException($e) && config('app.debug')) {
			return $this->toIlluminateResponse($this->convertExceptionToResponse($e), $e);
		}

		if (! $this->isHttpException($e)) {
			$e = new HttpException(500, $e->getMessage());
		}

		return $this->toIlluminateResponse(
			$this->renderHttpException($e), $e
		);
	}


	/**
	 * Determine if the given exception is an HTTP exception.
	 *  判断
	 * @param  \Throwable  $e
	 * @return bool
	 */
	protected function isHttpException(Throwable $e)
	{
		return $e instanceof HttpExceptionInterface;
	}


	/**
	 * Map the given exception into an Illuminate response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Response  $response
	 * @param  \Throwable  $e
	 * @return \Illuminate\Http\Response
	 */
	protected function toIlluminateResponse($response, Throwable $e)
	{
		// 重定向
		if ($response instanceof SymfonyRedirectResponse) {
			$response = new RedirectResponse(
				$response->getTargetUrl(), $response->getStatusCode(), $response->headers->all()
			);
		} else {
			$response = new Response(
				$response->getContent(), $response->getStatusCode(), $response->headers->all()
			);
		}

		return $response->withException($e);
	}


	/**
	 * Render the given HttpException.
	 *
	 * @param  \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface  $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function renderHttpException(HttpExceptionInterface $e)
	{
		// 报错页面
//		$this->registerErrorViewPaths();
//
//		if (view()->exists($view = $this->getHttpExceptionView($e))) {
//			return response()->view($view, [
//				'errors' => new ViewErrorBag,
//				'exception' => $e,
//			], $e->getStatusCode(), $e->getHeaders());
//		}

		return $this->convertExceptionToResponse($e);
	}


	/**
	 * Create a Symfony response for the given exception.
	 *
	 * @param  \Throwable  $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function convertExceptionToResponse(Throwable $e)
	{
		return SymfonyResponse::create(
			$this->renderExceptionContent($e),
			$this->isHttpException($e) ? $e->getStatusCode() : 500,
			$this->isHttpException($e) ? $e->getHeaders() : []
		);
	}

	protected function renderExceptionContent(Throwable $e)
	{
		try {
			return config('app.debug') && class_exists(Whoops::class)
				? $this->renderExceptionWithWhoops($e)
				: $this->renderExceptionWithSymfony($e, config('app.debug'));
		} catch (Exception $e) {
			return $this->renderExceptionWithSymfony($e, config('app.debug'));
		}
	}

	protected function renderExceptionWithSymfony(Throwable $e, $debug)
	{
		$renderer = new HtmlErrorRenderer($debug);

		return $renderer->render($e)->getAsString();
	}





	/**
	 * @inheritDoc
	 */
	public function renderForConsole($output, Throwable $e)
	{
		// TODO: Implement renderForConsole() method.
	}
}
