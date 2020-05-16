<?php


namespace Lufeijun1234\Foundation;


use Exception;
use Lufeijun1234\Filesystem\Filesystem;

class ProviderRepository
{
	/**
	 * The application implementation.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * The filesystem instance.
	 *  文件接口
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * The path to the manifest file.
	 *
	 * @var string
	 */
	protected $manifestPath;


	public function  __construct( Application $app , Filesystem $files, $manifestPath )
	{
		$this->app = $app;
		$this->files = $files;
		$this->manifestPath = $manifestPath;
	}


	/**
	 * Register the application service providers.
	 *  注册服务提供者
	 * @param array $providers
	 * @return void
	 * @throws \Lufeijun1234\Filesystem\FileNotFoundException
	 */
	public function load(array $providers)
	{
		/**
		 * $manifest 结构，具体可以查看 bootstrap/cache/services.php 文件
		 *  [
		 *		'when' => [],
		 *		'providers' => [],
		 *		'eager' => [],
		 *		'deferred' => [],
		 *	]
		 */
		$manifest = $this->loadManifest();

		//
		if ($this->shouldRecompile($manifest, $providers)) {
			$manifest = $this->compileManifest($providers);
		}


		// 对于满足特定的条件才进行注册的服务提供者
		foreach ($manifest['when'] as $provider => $events) {
			$this->registerLoadEvents($provider, $events);
		}

		// 注册每次都会用到的服务提供者
		foreach ($manifest['eager'] as $provider) {
			$this->app->register($provider);
		}

		$this->app->addDeferredServices($manifest['deferred']);
	}


	/**
	 * Load the service provider manifest JSON file.
	 *  加载缓存
	 * @return array|null
	 * @throws \Lufeijun1234\Filesystem\FileNotFoundException
	 */
	public function loadManifest()
	{
		// 判断是否有缓存文件，
		if ($this->files->exists($this->manifestPath)) {
			$manifest = $this->files->getRequire($this->manifestPath);

			if ($manifest) {
				return array_merge(['when' => []], $manifest);
			}
		}
	}


	/**
	 * Determine if the manifest should be compiled.
	 *  判断是否需要重新创建缓存文件
	 * @param  array  $manifest
	 * @param  array  $providers
	 * @return bool
	 */
	public function shouldRecompile($manifest, $providers)
	{
		return is_null($manifest) || $manifest['providers'] != $providers;
	}


	/**
	 * Compile the application service manifest file.
	 *
	 * @param array $providers
	 * @return array
	 * @throws Exception
	 */
	protected function compileManifest($providers)
	{
		// 构造格式化的数据
		$manifest = $this->freshManifest($providers);

		foreach ($providers as $provider) {
			$instance = $this->createProvider($provider);

			// 判断是不是延迟加载的服务提供者
			if ($instance->isDeferred()) {
				foreach ($instance->provides() as $service) {
					$manifest['deferred'][$service] = $provider;
				}

				$manifest['when'][$provider] = $instance->when();
			} else {
				// 每次 http 请求都是注册
				$manifest['eager'][] = $provider;
			}
		}

		return $this->writeManifest($manifest);
	}


	/**
	 * Create a fresh service manifest data structure.
	 *
	 * @param  array  $providers
	 * @return array
	 */
	protected function freshManifest(array $providers)
	{
		return ['providers' => $providers, 'eager' => [], 'deferred' => []];
	}


	/**
	 * Create a new provider instance.
	 *
	 * @param  string  $provider
	 * @return ServiceProvider
	 */
	public function createProvider($provider)
	{
		return new $provider($this->app);
	}


	/**
	 * Write the service manifest file to disk.
	 *
	 * @param  array  $manifest
	 * @return array
	 *
	 * @throws \Exception
	 */
	public function writeManifest($manifest)
	{
		if (! is_writable($dirname = dirname($this->manifestPath))) {
			throw new Exception("The {$dirname} directory must be present and writable.");
		}

		$this->files->replace(
			$this->manifestPath, '<?php return '.var_export($manifest, true).';'
		);

		return array_merge(['when' => []], $manifest);
	}


	/**
	 * Register the load events for the given provider.
	 *  监听对应事件，条件满足之后才会注册服务提供者
	 * @param string $provider
	 * @param array $events
	 * @return void
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws \ReflectionException
	 */
	protected function registerLoadEvents($provider, array $events)
	{
		if (count($events) < 1) {
			return;
		}

		$this->app->make('events')->listen($events, function () use ($provider) {
			$this->app->register($provider);
		});
	}

}
