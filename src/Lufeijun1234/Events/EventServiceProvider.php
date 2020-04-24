<?php
namespace Lufeijun1234\Events;

use Lufeijun1234\Abstracts\Serviceprovider\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('events', function ($app) {
			return (new Dispatcher($app));
		});
	}
}
