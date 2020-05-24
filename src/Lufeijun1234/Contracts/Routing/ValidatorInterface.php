<?php


namespace Lufeijun1234\Contracts\Routing;


use Lufeijun1234\Http\Request;
use Lufeijun1234\Routing\Route;

interface ValidatorInterface
{

	/**
	 * Validate a given rule against a route and request.
	 *
	 * @param  Route  $route
	 * @param  Request  $request
	 * @return bool
	 */
	public function matches(Route $route, Request $request);
}
