<?php

namespace Lufeijun1234\Contracts\Console;

interface Kernel
{
	/**
	 * Bootstrap the application for artisan commands.
	 * 启动命令
	 * @return void
	 */
	public function bootstrap();

	/**
	 * Handle an incoming console command.
	 * 处理 console 请求
	 * @param  \Symfony\Component\Console\Input\InputInterface  $input
	 * @param  \Symfony\Component\Console\Output\OutputInterface|null  $output
	 * @return int
	 */
	public function handle($input, $output = null);

	/**
	 * Run an Artisan console command by name.
	 * 调用
	 * @param  string  $command
	 * @param  array  $parameters
	 * @param  \Symfony\Component\Console\Output\OutputInterface|null  $outputBuffer
	 * @return int
	 */
	public function call($command, array $parameters = [], $outputBuffer = null);


	/**
	 * Queue an Artisan console command by name.
	 *  队列执行命令
	 * @param  string  $command
	 * @param  array  $parameters
	 * @return \Illuminate\Foundation\Bus\PendingDispatch
	 */
	public function queue($command, array $parameters = []);

	/**
	 * Get all of the commands registered with the console.
	 * 获取所有命令
	 * @return array
	 */
	public function all();

	/**
	 * Get the output for the last run command.
	 *
	 * @return string
	 */
	public function output();

	/**
	 * Terminate the application.
	 * 结束请求，收尾工作
	 * @param  \Symfony\Component\Console\Input\InputInterface  $input
	 * @param  int  $status
	 * @return void
	 */
	public function terminate($input, $status);


}
