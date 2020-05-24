<?php


namespace Lufeijun1234\Routing\Matching;


use Lufeijun1234\Contracts\Routing\ValidatorInterface;
use Lufeijun1234\Http\Request;
use Lufeijun1234\Routing\Route;

class HostValidator implements ValidatorInterface
{

	/**
	 * @inheritDoc
	 */
	public function matches(Route $route, Request $request)
	{
		$hostRegex = $route->getCompiled()->getHostRegex();

		if (is_null($hostRegex)) {
			return true;
		}

		return preg_match($hostRegex, $request->getHost());
	}
}
