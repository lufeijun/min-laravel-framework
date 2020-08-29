<?php

namespace Lufeijun1234\Foundation\Bootstrap;


use Exception;
use Lufeijun1234\Config\Repository;
use Lufeijun1234\Contracts\Config\RepositoryContract;
use Lufeijun1234\Foundation\Application;

use SplFileInfo;
use Symfony\Component\Finder\Finder;


class LoadConfiguration
{

	public function bootstrap(Application $app)
	{
		$items = [];


		// 文件缓存 待定
		if ( 0 && file_exists($cached = $app->getCachedConfigPath())) {
			$items = require $cached;

			$loadedFromCache = true;
		}


		// Next we will spin through all of the configuration files in the configuration
		// directory and load each one into the repository. This will make all of the
		// options available to the developer for use in various parts of this app.
		$app->instance('config', $config = new Repository($items));


		// 加载变量
		if (! isset($loadedFromCache)) {
			$this->loadConfigurationFiles($app, $config);
		}


		$app->detectEnvironment(function () use ($config) {
			return $config->get('app.env', 'production');
		});


		date_default_timezone_set($config->get('app.timezone', 'UTC'));

		mb_internal_encoding('UTF-8');

	}


	/**
	 * Load the configuration items from all of the files.
	 *
	 * @param Application $app
	 * @param RepositoryContract $repository
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function loadConfigurationFiles(Application $app, RepositoryContract $repository)
	{
		$files = $this->getConfigurationFiles($app);


		if ( ! isset($files['app']) ) {
			throw new Exception('Unable to load the "app" configuration file.');
		}

		foreach ($files as $key => $path) {
			$repository->set($key, require $path);
		}
	}


	/**
	 * Get all of the configuration files for the application.
	 *
	 * @param  Application  $app
	 * @return array
	 */
	protected function getConfigurationFiles(Application $app)
	{
		$files = [];
		$configPath = realpath($app->configPath());
		foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
			$directory = $this->getNestedDirectory($file, $configPath);
			$files[$directory.basename($file->getRealPath(), '.php')] = $file->getRealPath();
		}
		ksort($files, SORT_NATURAL);
		return $files;
	}


	/**
	 * Get the configuration file nesting path.
	 *  aaa/bbb/ccc  => aaa.bbb.ccc
	 * @param  \SplFileInfo  $file
	 * @param  string  $configPath
	 * @return string
	 */
	protected function getNestedDirectory(SplFileInfo $file, $configPath)
	{
		$directory = $file->getPath();
		if ($nested = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
			$nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
		}

		return $nested;
	}

}
