<?php


namespace Lufeijun1234\Foundation;


use Closure;
use Lufeijun1234\Support\Str;

class EnvironmentDetector
{
	public function detect(Closure $callback, $consoleArgs = null)
	{
		// 命令台
		if ($consoleArgs) {
			return $this->detectConsoleEnvironment($callback, $consoleArgs);
		}

		return $this->detectWebEnvironment($callback);
	}

	// web 请求
	protected function detectWebEnvironment(Closure $callback)
	{
		return $callback();
	}

	// 控制台
	protected function detectConsoleEnvironment(Closure $callback, array $args)
	{
		// First we will check if an environment argument was passed via console arguments
		// and if it was that automatically overrides as the environment. Otherwise, we
		// will check the environment as a "web" request like a typical HTTP request.
		if (! is_null($value = $this->getEnvironmentArgument($args))) {
			return $value;
		}

		return $this->detectWebEnvironment($callback);
	}

	protected function getEnvironmentArgument(array $args)
	{
		foreach ($args as $i => $value) {
			if ($value === '--env') {
				return $args[$i + 1] ?? null;
			}

			if (Str::startsWith($value, '--env')) {
				return head(array_slice(explode('=', $value), 1));
			}
		}
	}


}
