<?php


namespace Lufeijun1234\Http;

use Lufeijun1234\Traits\Macroable;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class Response extends SymfonyResponse
{

	use ResponseTrait, Macroable {
		Macroable::__call as macroCall;
	}


	/**
	 * Create a new HTTP response.
	 *
	 * @param  mixed  $content
	 * @param  int  $status
	 * @param  array  $headers
	 * @return void
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($content = '', $status = 200, array $headers = [])
	{
		$this->headers = new ResponseHeaderBag($headers);

		$this->setContent($content);
		$this->setStatusCode($status);
		$this->setProtocolVersion('1.0');
	}
}
