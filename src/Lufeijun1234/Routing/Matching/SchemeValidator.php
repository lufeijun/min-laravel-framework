<?php


namespace Lufeijun1234\Routing\Matching;


use Lufeijun1234\Contracts\Routing\ValidatorInterface;
use Lufeijun1234\Http\Request;
use Lufeijun1234\Routing\Route;

class SchemeValidator implements ValidatorInterface
{

	/**
	 * @inheritDoc
	 */
	public function matches(Route $route, Request $request)
	{
		if ($route->httpOnly()) {
			return ! $request->secure();
		} elseif ($route->secure()) {
			return $request->secure();
		}

		return true;
	}
}
