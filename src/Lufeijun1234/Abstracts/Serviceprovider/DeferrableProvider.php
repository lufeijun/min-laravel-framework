<?php


namespace Lufeijun1234\Abstracts\Serviceprovider;


interface DeferrableProvider
{
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides();
}
