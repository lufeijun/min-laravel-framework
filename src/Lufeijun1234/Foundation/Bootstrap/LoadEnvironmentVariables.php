<?php
namespace Lufeijun1234\Foundation\Bootstrap;


use Lufeijun1234\Foundation\Application;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;

use Lufeijun1234\Support\Env;



class LoadEnvironmentVariables
{

	/**
	 * Bootstrap the given application.
	 *
	 * @param  Application  $app
	 * @return void
	 */
	public function bootstrap( Application $app )
	{
		// 处理文件缓存问题
		if ( 0 &&  $app->configurationIsCached()) {
			return;
		}

		// 检测一些什么东西
		// $this->checkForSpecificEnvironmentFile($app);

		try {
			$this->createDotenv($app)->safeLoad();
		} catch (InvalidFileException $e) {
			$this->writeErrorAndDie($e);
		}
	}



	/**
	 * Detect if a custom environment file matching the APP_ENV exists.
	 *
	 * @param  Application  $app
	 * @return void
	 */
	protected function checkForSpecificEnvironmentFile($app)
	{
		// 命令行运行的，待定
		if ($app->runningInConsole() && ($input = new ArgvInput)->hasParameterOption('--env')) {
			if ($this->setEnvironmentFilePath(
				$app, $app->environmentFile().'.'.$input->getParameterOption('--env')
			)) {
				return;
			}
		}

		$environment = Env::get('APP_ENV');

		if (! $environment) {
			return;
		}

		$this->setEnvironmentFilePath(
			$app, $app->environmentFile().'.'.$environment
		);
	}

	/**
	 * Write the error information to the screen and exit.
	 *
	 * @param  \Dotenv\Exception\InvalidFileException  $e
	 * @return void
	 */
	protected function writeErrorAndDie(InvalidFileException $e)
	{
		//$output = (new ConsoleOutput)->getErrorOutput();

		//$output->writeln('The environment file is invalid!');
		// $output->writeln($e->getMessage());

		echo 'The environment file is invalid!<br>';

		echo $e->getMessage();

		die(1);
	}


	/**
	 * Create a Dotenv instance.
	 *  初始化 Dotenv 实例
	 * @param Application  $app
	 * @return \Dotenv\Dotenv
	 */
	protected function createDotenv($app)
	{
		return Dotenv::create(
			Env::getRepository(),
			$app->environmentPath(),
			$app->environmentFile()
		);
	}


}
