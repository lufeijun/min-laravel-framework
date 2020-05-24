<?php


namespace Lufeijun1234\Routing\Matching;


use Lufeijun1234\Contracts\Routing\ValidatorInterface;
use Lufeijun1234\Http\Request;
use Lufeijun1234\Routing\Route;

class UriValidator implements ValidatorInterface
{

	/**
	 * @inheritDoc
	 */
	public function matches(Route $route, Request $request)
	{
		$path = rtrim($request->getPathInfo(), '/') ?: '/';

		return preg_match($route->getCompiled()->getRegex(), rawurldecode($path));
	}
}
