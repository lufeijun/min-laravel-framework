<?php


namespace Lufeijun1234\Http\Concerns;


trait InteractsWithInput
{
	/**
	 * Retrieve a header from the request.
	 *
	 * @param  string|null  $key
	 * @param  string|array|null  $default
	 * @return string|array|null
	 */
	public function header($key = null, $default = null)
	{
		return $this->retrieveItem('headers', $key, $default);
	}


	/**
	 * Retrieve a parameter item from a given source.
	 *
	 * @param  string  $source
	 * @param  string  $key
	 * @param  string|array|null  $default
	 * @return string|array|null
	 */
	protected function retrieveItem($source, $key, $default)
	{
		if (is_null($key)) {
			return $this->$source->all();
		}

		return $this->$source->get($key, $default);
	}
}
