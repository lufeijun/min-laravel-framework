<?php

namespace Lufeijun1234\Http;


use Closure;
use Lufeijun1234\Http\Concerns\InteractsWithInput;
use Lufeijun1234\Http\Concerns\InteractsWithContentTypes;
use Lufeijun1234\Traits\Macroable;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request extends SymfonyRequest
{

	use Macroable;
	use InteractsWithContentTypes,InteractsWithInput;


	/**
	 * The route resolver callback.
	 *
	 * @var \Closure
	 */
	protected $routeResolver;

	/**
	 * Create a new Illuminate HTTP request from server variables.
	 *  创建请求变量
	 * @return static
	 */
	public static function capture()
	{
		static::enableHttpMethodParameterOverride();

		return static::createFromBase(SymfonyRequest::createFromGlobals());
	}



	/**
	 * Create an Illuminate request from a Symfony instance.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return static
	 */
	public static function createFromBase(SymfonyRequest $request)
	{
		$newRequest = (new static)->duplicate(
			$request->query->all(), $request->request->all(), $request->attributes->all(),
			$request->cookies->all(), $request->files->all(), $request->server->all()
		);

		$newRequest->headers->replace($request->headers->all());

		$newRequest->content = $request->content;

		$newRequest->request = $newRequest->getInputSource();

		return $newRequest;
	}



	/**
	 * Get the input source for the request.
	 *  获取请求参数
	 * @return \Symfony\Component\HttpFoundation\ParameterBag
	 */
	protected function getInputSource()
	{
		if ($this->isJson()) {
			return $this->json();
		}

		return in_array($this->getRealMethod(), ['GET', 'HEAD']) ? $this->query : $this->request;
	}


	/**
	 * Get the request method.
	 *
	 * @return string
	 */
	public function method()
	{
		return $this->getMethod();
	}


	/**
	 * Get the current decoded path info for the request.
	 *
	 * @return string
	 */
	public function decodedPath()
	{
		return rawurldecode($this->path());
	}


	/**
	 * Get the current path info for the request.
	 *
	 * @return string
	 */
	public function path()
	{
		$pattern = trim($this->getPathInfo(), '/');

		return $pattern == '' ? '/' : $pattern;
	}

	/**
	 * Set the route resolver callback.
	 *
	 * @param  \Closure  $callback
	 * @return $this
	 */
	public function setRouteResolver(Closure $callback)
	{
		$this->routeResolver = $callback;

		return $this;
	}

}
