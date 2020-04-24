<?php

namespace Lufeijun1234\Contracts\Events;


interface DispatcherContract
{
	/**
	 * Register an event listener with the dispatcher.
	 *  注册监听程序
	 *
	 * @param  string|array  $events
	 * @param  \Closure|string  $listener
	 * @return void
	 */
	public function listen($events, $listener);

	/**
	 * Determine if a given event has listeners.
	 *  判断事件是否有监听
	 * @param  string  $eventName
	 * @return bool
	 */
	public function hasListeners($eventName);

	/**
	 * Register an event subscriber with the dispatcher.
	 *  事件订阅者，事件发生后需要处理的程序
	 * @param  object|string  $subscriber
	 * @return void
	 */
	public function subscribe($subscriber);

	/**
	 * Dispatch an event until the first non-null response is returned.
	 *
	 * @param  string|object  $event
	 * @param  mixed  $payload
	 * @return array|null
	 */
	public function until($event, $payload = []);

	/**
	 * Dispatch an event and call the listeners.
	 *  调度事件
	 * @param  string|object  $event
	 * @param  mixed  $payload
	 * @param  bool  $halt
	 * @return array|null
	 */
	public function dispatch($event, $payload = [], $halt = false);

	/**
	 * Register an event and payload to be fired later.
	 *
	 * @param  string  $event
	 * @param  array  $payload
	 * @return void
	 */
	public function push($event, $payload = []);

	/**
	 * Flush a set of pushed events.
	 *  刷新
	 * @param  string  $event
	 * @return void
	 */
	public function flush($event);

	/**
	 * Remove a set of listeners from the dispatcher.
	 *
	 * @param  string  $event
	 * @return void
	 */
	public function forget($event);

	/**
	 * Forget all of the queued listeners.
	 *
	 * @return void
	 */
	public function forgetPushed();
}
