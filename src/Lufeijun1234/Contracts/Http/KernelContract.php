<?php


namespace Lufeijun1234\Contracts\Http;


interface KernelContract
{
	/**
	 * Bootstrap the application for HTTP requests.
	 *  启动 application
	 * @return void
	 */
	public function bootstrap();

	/**
	 * Handle an incoming HTTP request.
	 * 处理请求，并返回响应
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function handle($request);

	/**
	 * Perform any final actions for the request lifecycle.
	 *  处理善后工作
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  \Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function terminate($request, $response);

	/**
	 * Get the Laravel application instance.
	 *  获取 APP
	 * @return Lufeijun1234\Foundation\Application
	 */
	public function getApplication();
}
