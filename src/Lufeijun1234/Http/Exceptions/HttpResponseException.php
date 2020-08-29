<?php


namespace Lufeijun1234\Http\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use RuntimeException;

class HttpResponseException extends RuntimeException
{

	protected $response;

	public function __construct(Response $response)
	{
		$this->response = $response;
	}

	public function getResponse()
	{
		return $this->response;
	}
}
