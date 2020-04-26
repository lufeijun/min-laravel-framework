<?php


namespace Lufeijun1234\Http\Concerns;


use Lufeijun1234\Support\Str;

trait InteractsWithContentTypes
{

	/**
	 * Determine if the request is sending JSON.
	 *
	 * @return bool
	 */
	public function isJson()
	{
		return Str::contains($this->header('CONTENT_TYPE'), ['/json', '+json']);
	}
}
