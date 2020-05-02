<?php


namespace Lufeijun1234\Foundation;


class AliasLoader
{
	/**
	 * The singleton instance of the loader.
	 *
	 * @var AliasLoader
	 */
	protected static $instance;


	/**
	 * The array of class aliases.
	 *
	 * @var array
	 */
	protected $aliases;


	/**
	 * Indicates if a loader has been registered.
	 *
	 * @var bool
	 */
	protected $registered = false;


	/**
	 * The namespace for all real-time facades.
	 *
	 * @var string
	 */
	protected static $facadeNamespace = 'Facades\\';




	/**
	 * Create a new AliasLoader instance.
	 *  构造函数私有化，实现单列模式
	 * @param  array  $aliases
	 * @return void
	 */
	private function __construct($aliases)
	{
		$this->aliases = $aliases;
	}


	/**
	 * Get or create the singleton alias loader instance.
	 *  单列模式
	 * @param  array  $aliases
	 * @return AliasLoader
	 */
	public static function getInstance(array $aliases = [])
	{
		if (is_null(static::$instance)) {
			return static::$instance = new static($aliases);
		}

		$aliases = array_merge(static::$instance->getAliases(), $aliases);

		static::$instance->setAliases($aliases);

		return static::$instance;
	}


	/**
	 * Get the registered aliases.
	 *
	 * @return array
	 */
	public function getAliases()
	{
		return $this->aliases;
	}


	/**
	 * Set the registered aliases.
	 *
	 * @param  array  $aliases
	 * @return void
	 */
	public function setAliases(array $aliases)
	{
		$this->aliases = $aliases;
	}



	/**
	 * Register the loader on the auto-loader stack.
	 *
	 * @return void
	 */
	public function register()
	{
		if (! $this->registered) {
			$this->prependToLoaderStack();
			$this->registered = true;
		}
	}


	/**
	 * Prepend the load method to the auto-loader stack.
	 *
	 * @return void
	 */
	protected function prependToLoaderStack()
	{
		spl_autoload_register([$this, 'load'], true, true);
	}


	/**
	 * Load a class alias if it is registered.
	 *  加载类
	 * @param string $alias
	 * @return bool|null
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws \ReflectionException
	 */
	public function load($alias)
	{
		if (static::$facadeNamespace && strpos($alias, static::$facadeNamespace) === 0) {
			$this->loadFacade($alias);
			return true;
		}

		if (isset($this->aliases[$alias])) {
			return class_alias($this->aliases[$alias], $alias);
		}
	}

	/**
	 * Load a real-time facade for the given alias.
	 *
	 * @param string $alias
	 * @return void
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws \ReflectionException
	 */
	protected function loadFacade($alias)
	{
		require $this->ensureFacadeExists($alias);
	}


	/**
	 * Ensure that the given alias has an existing real-time facade class.
	 *
	 * @param string $alias
	 * @return string
	 * @throws \Lufeijun1234\Container\BindingResolutionException
	 * @throws \ReflectionException
	 */
	protected function ensureFacadeExists($alias)
	{
		// 文件缓存
		if ( file_exists($path = storage_path('framework/cache/facade-'.sha1($alias).'.php'))) {
			return $path;
		}

		file_put_contents($path, $this->formatFacadeStub(
			$alias, file_get_contents(__DIR__.'/stubs/facade.stub')
		));

		return $path;
	}


	/**
	 * Format the facade stub with the proper namespace and class.
	 *  按照模板，自动生成一个 facade 类
	 * @param  string  $alias
	 * @param  string  $stub
	 * @return string
	 */
	protected function formatFacadeStub($alias, $stub)
	{
		$replacements = [
			str_replace('/', '\\', dirname(str_replace('\\', '/', $alias))),
			class_basename($alias),
			substr($alias, strlen(static::$facadeNamespace)),
		];

		return str_replace(
			['DummyNamespace', 'DummyClass', 'DummyTarget'], $replacements, $stub
		);
	}

}
