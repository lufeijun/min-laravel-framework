<?php


namespace Lufeijun1234\Routing;


class UrlGenerator
{
	/**
	 * The root namespace being applied to controller actions.
	 *
	 * @var string
	 */
	protected $rootNamespace;






	public function setRootControllerNamespace($rootNamespace)
	{
		$this->rootNamespace = $rootNamespace;
		return $this;
	}
}
