<?php


namespace Lufeijun1234\Http\Concerns;


use Lufeijun1234\Support\Str;

trait InteractsWithContentTypes
{

	/**
	 * Determine if the request is sending JSON.
	 *  判断当前请求是否发送了 json 串
	 * @return bool
	 */
	public function isJson()
	{
		return Str::contains($this->header('CONTENT_TYPE'), ['/json', '+json']);
	}


	/**
	 * Determine if the current request probably expects a JSON response.
	 *  判断当前请求是否期待返回 json
	 * @return bool
	 */
	public function expectsJson()
	{
		return ($this->ajax() && ! $this->pjax() && $this->acceptsAnyContentType()) || $this->wantsJson();
	}

	/**
	 * Determine if the current request is asking for JSON.
	 *
	 * @return bool
	 */
	public function wantsJson()
	{
		$acceptable = $this->getAcceptableContentTypes();

		return isset($acceptable[0]) && Str::contains($acceptable[0], ['/json', '+json']);
	}


	/**
	 * Determine if the current request accepts any content type.
	 *  判断当前请求是否接受任意类型的数据
	 * @return bool
	 */
	public function acceptsAnyContentType()
	{
		$acceptable = $this->getAcceptableContentTypes();

		return count($acceptable) === 0 || (
				isset($acceptable[0]) && ($acceptable[0] === '*/*' || $acceptable[0] === '*')
			);
	}

}
