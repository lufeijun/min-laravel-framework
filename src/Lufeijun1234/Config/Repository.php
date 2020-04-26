<?php


namespace Lufeijun1234\Config;


use Lufeijun1234\Contracts\Config\RepositoryContract;
use Lufeijun1234\Support\Arr;

class Repository implements \ArrayAccess, RepositoryContract
{

	/**
	 * All of the configuration items.
	 *
	 * @var array
	 */
	protected $items = [];



	/**
	 * Create a new configuration repository.
	 *
	 * @param  array  $items
	 * @return void
	 */
	public function __construct(array $items = [])
	{
		$this->items = $items;
	}



	/**
	 * @inheritDoc
	 */
	public function offsetExists($offset)
	{
		// TODO: Implement offsetExists() method.
	}

	/**
	 * @inheritDoc
	 */
	public function offsetGet($offset)
	{
		// TODO: Implement offsetGet() method.
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet($offset, $value)
	{
		// TODO: Implement offsetSet() method.
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset($offset)
	{
		// TODO: Implement offsetUnset() method.
	}

	/**
	 * @inheritDoc
	 */
	public function has($key)
	{
		return Arr::has($this->items, $key);
	}

	/**
	 * @inheritDoc
	 */
	public function get($key, $default = null)
	{
		if (is_array($key)) {
			return $this->getMany($key);
		}

		return Arr::get($this->items, $key, $default);
	}

	/**
	 * Get many configuration values.
	 *
	 * @param  array  $keys
	 * @return array
	 */
	public function getMany($keys)
	{
		$config = [];

		foreach ($keys as $key => $default) {
			if (is_numeric($key)) {
				[$key, $default] = [$default, null];
			}

			$config[$key] = Arr::get($this->items, $key, $default);
		}

		return $config;
	}

	/**
	 * @inheritDoc
	 */
	public function all()
	{
		// TODO: Implement all() method.
	}

	/**
	 * @inheritDoc
	 */
	public function set($key, $value = null)
	{
		$keys = is_array($key) ? $key : [$key => $value];

		foreach ($keys as $key => $value) {
			Arr::set($this->items, $key, $value);
		}

	}

	/**
	 * @inheritDoc
	 */
	public function prepend($key, $value)
	{
		// TODO: Implement prepend() method.
	}

	/**
	 * @inheritDoc
	 */
	public function push($key, $value)
	{
		// TODO: Implement push() method.
	}
}
