<?php


namespace Lufeijun1234\Http;

use Lufeijun1234\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Throwable;

trait ResponseTrait
{
	/**
	 * The original content of the response.
	 *
	 * @var mixed
	 */
	public $original;

	/**
	 * The exception that triggered the error response (if applicable).
	 *
	 * @var \Throwable|null
	 */
	public $exception;

	/**
	 * Get the status code for the response.
	 *
	 * @return int
	 */
	public function status()
	{
		return $this->getStatusCode();
	}


	public function content()
	{
		return $this->getContent();
	}

	public function getOriginalContent()
	{
		$original = $this->original;

		return $original instanceof self ? $original->{__FUNCTION__}() : $original;
	}

	public function header($key, $values, $replace = true)
	{
		$this->headers->set($key, $values, $replace);

		return $this;
	}

	public function withHeaders($headers)
	{
		if ($headers instanceof HeaderBag) {
			$headers = $headers->all();
		}

		foreach ($headers as $key => $value) {
			$this->headers->set($key, $value);
		}

		return $this;
	}


	public function cookie($cookie)
	{
		return call_user_func_array([$this, 'withCookie'], func_get_args());
	}


	public function withCookie($cookie)
	{
		if (is_string($cookie) && function_exists('cookie')) {
			$cookie = call_user_func_array('cookie', func_get_args());
		}

		$this->headers->setCookie($cookie);

		return $this;
	}

	public function getCallback()
	{
		return $this->callback ?? null;
	}


	public function withException(Throwable $e)
	{
		$this->exception = $e;

		return $this;
	}

	public function throwResponse()
	{
		throw new HttpResponseException($this);
	}

}
