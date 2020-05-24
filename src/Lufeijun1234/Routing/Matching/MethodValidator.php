<?php


namespace Lufeijun1234\Routing\Matching;


use Lufeijun1234\Contracts\Routing\ValidatorInterface;
use Lufeijun1234\Http\Request;
use Lufeijun1234\Routing\Route;

class MethodValidator implements ValidatorInterface
{

	/**
	 * @inheritDoc
	 */
	public function matches(Route $route, Request $request)
	{
		return in_array($request->getMethod(), $route->methods());
	}
}
