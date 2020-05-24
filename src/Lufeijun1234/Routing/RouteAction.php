<?php


namespace Lufeijun1234\Routing;


use LogicException;
use Lufeijun1234\Support\Str;
use UnexpectedValueException;

class RouteAction
{
	/**
	 * Parse the given action into an array.
	 *
	 * @param  string  $uri
	 * @param  mixed  $action
	 * @return array
	 */
	public static function parse($uri, $action)
	{
		// 没有设置 action 时，处理办法
		if (is_null($action)) {
			return static::missingAction($uri);
		}

		// action 是一个闭包时，action 如果是数组的话，就是 [Obj,method]
		if (is_callable($action, true)) {
			return ! is_array($action) ? ['uses' => $action] : [
				'uses' => $action[0].'@'.$action[1],
				'controller' => $action[0].'@'.$action[1],
			];
		} elseif (! isset($action['uses'])) {
			// 在 action 中找到一个可执行的闭包函数，作为路由对应的处理函数
			$action['uses'] = static::findCallable($action);
		}

		// 如果 uses 的格式不为 obj@method 是，需要做一步处理
		if (is_string($action['uses']) && ! Str::contains($action['uses'], '@')) {
			$action['uses'] = static::makeInvokable($action['uses']);
		}

		return $action;
	}


	/**
	 * Get an action for a route that has no action.
	 *  没有穿 action 时，在编译时不报错，而是封装一个异常类函数，等有访问时才返回错误
	 * @param  string  $uri
	 * @return array
	 *
	 * @throws \LogicException
	 */
	protected static function missingAction($uri)
	{
		return ['uses' => function () use ($uri) {
			throw new LogicException("Route for [{$uri}] has no action.");
		}];
	}


	/**
	 * Find the callable in an action array.
	 *  在数组中找闭包
	 * @param  array  $action
	 * @return callable
	 */
	protected static function findCallable(array $action)
	{
		return Arr::first($action, function ($value, $key) {
			return is_callable($value) && is_numeric($key);
		});
	}


	/**
	 * Make an action for an invokable controller.
	 *  __invoke php 的魔术方法，可以像调用函数一样去调用对象 ex: obj();
	 * @param  string  $action
	 * @return string
	 *
	 * @throws \UnexpectedValueException
	 */
	protected static function makeInvokable($action)
	{
		if (! method_exists($action, '__invoke')) {
			throw new UnexpectedValueException("Invalid route action: [{$action}].");
		}

		return $action.'@__invoke';
	}


}
