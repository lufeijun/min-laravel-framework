<?php


namespace Lufeijun1234\Contracts\Debug;

use Throwable;


interface ExceptionHandler
{

	/**
	 * Report or log an exception.
	 *  上报       日志
	 * @param  \Throwable  $e
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function report(Throwable $e);



	/**
	 * Determine if the exception should be reported.
	 *
	 * @param  \Throwable  $e
	 * @return bool
	 */
	public function shouldReport(Throwable $e);


	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Throwable  $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 *
	 * @throws \Throwable
	 */
	public function render($request, Throwable $e);

	/**
	 * Render an exception to the console.
	 *
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  \Throwable  $e
	 * @return void
	 */
	public function renderForConsole($output, Throwable $e);

}
