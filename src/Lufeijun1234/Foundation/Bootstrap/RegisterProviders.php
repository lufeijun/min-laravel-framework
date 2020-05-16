<?php


namespace Lufeijun1234\Foundation\Bootstrap;


use Lufeijun1234\Foundation\Application;

class RegisterProviders
{

	public function bootstrap(Application $app)
	{
		$app->registerConfiguredProviders();
	}
}
