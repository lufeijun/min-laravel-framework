<?php


namespace Lufeijun1234\Foundation\Bootstrap;


use Lufeijun1234\Facades\Facade;
use Lufeijun1234\Foundation\AliasLoader;
use Lufeijun1234\Foundation\Application;

class RegisterFacades
{

	public function bootstrap( Application $app )
	{
		Facade::clearResolvedInstances();

		Facade::setFacadeApplication( $app );

		AliasLoader::getInstance(array_merge(
			$app->make('config')->get('app.aliases', []),
			// $app->make(PackageManifest::class)->aliases()  扩展包自动发现机制
			[]
		))->register();

	}


}
