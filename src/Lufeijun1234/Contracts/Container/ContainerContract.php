<?php

namespace Lufeijun1234\Contracts\Container;

use Closure;

interface ContainerContract {

	/**
	 * 判断给定的抽象类名|接口名是否被绑定过实现类
	 *
	 * @param  string  $abstract  抽象类名称|接口类
	 * @return bool
	 */
	public function bound($abstract);


	/**
	 * 注册一个绑定关系
	 *
	 * @param  string  $abstract          抽象类
	 * @param  \Closure|string|null  $concrete  闭包|实体类
	 * @param  bool  $shared   是否为单利模式
	 * @return void
	 */
	public function bind($abstract, $concrete = null, $shared = false);

	/**
	 * 绑定单列模式
	 *
	 * @param  string  $abstract
	 * @param  \Closure|string|null  $concrete
	 * @return void
	 */
	public function singleton($abstract, $concrete = null);


	/**
	 * 将一个对象绑定到容器中
	 *
	 * @param  string  $abstract  抽象类
	 * @param  mixed  $instance   实现类
	 * @return mixed
	 */
	public function instance($abstract, $instance);


	/**
	 * F5 刷新机制
	 *
	 * @return void
	 */
	public function flush();

	/**
	 * 从容器中解析出需要的类
	 *
	 * @param  string  $abstract  抽象类名称
	 * @param  array  $parameters 需要的参数
	 * @return mixed
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function make($abstract, array $parameters = []);

	/**
	 * 调用闭包或者类方法，
	 *
	 * @param  callable|string  $callback
	 * @param  array  $parameters
	 * @param  string|null  $defaultMethod
	 * @return mixed
	 */
	public function call($callback, array $parameters = [], $defaultMethod = null);

	/**
	 * 判断抽象类是否被解析过
	 *
	 * @param  string  $abstract
	 * @return bool
	 */
	public function resolved($abstract);

	/**
	 * 注册一个解析回调函数
	 *
	 * @param  \Closure|string  $abstract
	 * @param  \Closure|null  $callback
	 * @return void
	 */
	public function resolving($abstract, Closure $callback = null);

	/**
	 * 注册一个解析回调函数
	 *
	 * @param  \Closure|string  $abstract
	 * @param  \Closure|null  $callback
	 * @return void
	 */
	public function afterResolving($abstract, Closure $callback = null);

}
