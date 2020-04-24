<?php


namespace Lufeijun1234\Events;


use Lufeijun1234\Support\Str;
use Lufeijun1234\Contracts\Container\ContainerContract;
use Lufeijun1234\Contracts\Events\DispatcherContract;
use Lufeijun1234\Support\Arr;
use Lufeijun1234\Traits\Macroable;

class Dispatcher implements DispatcherContract
{

	use Macroable;


	/**
	 * The IoC container instance.
	 *  框架容器
	 * @var Container
	 */
	protected $container;

	/**
	 * The registered event listeners.
	 *  注册的监听器
	 * @var array
	 */
	protected $listeners = [];

	/**
	 * The wildcard listeners.
	 *  带通配符的监听器
	 * @var array
	 */
	protected $wildcards = [];


	/**
	 * The cached wildcard listeners.
	 *  缓存    内存数组缓存
	 * @var array
	 */
	protected $wildcardsCache = [];


	public function __construct(ContainerContract $container = null)
	{
		$this->container = $container;
	}


	/**
	 * Register an event listener with the dispatcher.
	 *  注册事件
	 * @param  string|array  $events
	 * @param  \Closure|string  $listener
	 * @return void
	 */
	public function listen($events, $listener)
	{
		// 依据带不带通配符 * ，分情况处理
		foreach ((array) $events as $event) {
			if (Str::contains($event, '*')) {
				$this->setupWildcardListen($event, $listener);
			} else {
				$this->listeners[$event][] = $this->makeListener($listener);
			}
		}
	}


	/**
	 * Setup a wildcard listener callback.
	 *
	 * @param  string  $event
	 * @param  \Closure|string  $listener
	 * @return void
	 */
	protected function setupWildcardListen($event, $listener)
	{
		$this->wildcards[$event][] = $this->makeListener($listener, true);

		$this->wildcardsCache = [];
	}


	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @param  \Closure|string  $listener
	 * @param  bool  $wildcard
	 * @return \Closure
	 */
	public function makeListener($listener, $wildcard = false)
	{
		// 如果 listener 是字符串，表示改事件处理函数是个类
		if (is_string($listener)) {
			return $this->createClassListener($listener, $wildcard);
		}

		// listener 本身就是一个可执行闭包
		return function ($event, $payload) use ($listener, $wildcard) {
			if ($wildcard) {
				return $listener($event, $payload);
			}

			return $listener(...array_values($payload));
		};
	}

	/**
	 * Create a class based listener using the IoC container.
	 *  创建类监听器，都进行了闭包封装
	 * @param  string  $listener
	 * @param  bool  $wildcard
	 * @return \Closure
	 */
	public function createClassListener($listener, $wildcard = false)
	{
		return function ($event, $payload) use ($listener, $wildcard) {
			// 如果是带通配符的，这里把实际出发的事件名传递给函数了
			if ($wildcard) {
				return call_user_func($this->createClassCallable($listener), $event, $payload);
			}

			return call_user_func_array(
				$this->createClassCallable($listener), $payload
			);
		};
	}

	/**
	 * Create the class based event callable.
	 *
	 * @param  string  $listener
	 * @return callable
	 */
	protected function createClassCallable($listener)
	{
		[$class, $method] = $this->parseClassCallable($listener);

		// 这块是队列问题，暂时不考虑
		if ( 0 && $this->handlerShouldBeQueued($class)) {
			return $this->createQueuedHandlerCallable($class, $method);
		}

		return [$this->container->make($class), $method];
	}

	/**
	 * Parse the class listener into class and method.
	 *  解析 listener 字符串，期望值  XXX@xxx
	 * @param  string  $listener
	 * @return array
	 */
	protected function parseClassCallable($listener)
	{
		return Str::parseCallback($listener, 'handle');
	}


	/**
	 * @inheritDoc
	 */
	public function hasListeners($eventName)
	{
		// TODO: Implement hasListeners() method.
	}

	/**
	 * @inheritDoc
	 */
	public function subscribe($subscriber)
	{
		// TODO: Implement subscribe() method.
	}

	/**
	 * @inheritDoc
	 */
	public function until($event, $payload = [])
	{
		// TODO: Implement until() method.
	}

	/**
	 * Fire an event and call the listeners.
	 *  触发事件，并调用之前设置好的监听函数
	 * @param  string|object  $event
	 * @param  mixed  $payload
	 * @param  bool  $halt
	 * @return array|null
	 */
	public function dispatch($event, $payload = [], $halt = false)
	{
		// When the given "event" is actually an object we will assume it is an event
		// object and use the class as the event name and this event itself as the
		// payload to the handler, which makes object based events quite simple.
		// 这段话大意就是，如果 event 是个对象，那就就以对象名作为事件名，对象本身作为 listener 的参数
		[$event, $payload] = $this->parseEventAndPayload(
			$event, $payload
		);

		// 队列，放弃
		if ( 0 && $this->shouldBroadcast($payload)) {
			$this->broadcastEvent($payload[0]);
		}

		$responses = [];

		foreach ($this->getListeners($event) as $listener) {
			$response = $listener($event, $payload);

			// If a response is returned from the listener and event halting is enabled
			// we will just return this response, and not call the rest of the event
			// listeners. Otherwise we will add the response on the response list.
			if ($halt && ! is_null($response)) {
				return $response;
			}

			// If a boolean false is returned from a listener, we will stop propagating
			// the event to any further listeners down in the chain, else we keep on
			// looping through the listeners and firing every one in our sequence.
			if ($response === false) {
				break;
			}

			$responses[] = $response;
		}

		return $halt ? null : $responses;
	}


	/**
	 * Parse the given event and payload and prepare them for dispatching.
	 *  解析事件对象
	 * @param  mixed  $event
	 * @param  mixed  $payload
	 * @return array
	 */
	protected function parseEventAndPayload($event, $payload)
	{
		if (is_object($event)) {
			[$payload, $event] = [[$event], get_class($event)];
		}

		return [$event, Arr::wrap($payload)];
	}


	/**
	 * Get all of the listeners for a given event name.
	 *  获取事件对应的所有监听器
	 * @param  string  $eventName
	 * @return array
	 */
	public function getListeners($eventName)
	{
		$listeners = $this->listeners[$eventName] ?? [];

		$listeners = array_merge(
			$listeners,
			$this->wildcardsCache[$eventName] ?? $this->getWildcardListeners($eventName)
		);

		return class_exists($eventName, false)
			? $this->addInterfaceListeners($eventName, $listeners)
			: $listeners;
	}


	/**
	 * Get the wildcard listeners for the event.
	 *  获取带通配符事件类型的
	 * @param  string  $eventName
	 * @return array
	 */
	protected function getWildcardListeners($eventName)
	{
		$wildcards = [];

		foreach ($this->wildcards as $key => $listeners) {
			if (Str::is($key, $eventName)) {
				$wildcards = array_merge($wildcards, $listeners);
			}
		}

		return $this->wildcardsCache[$eventName] = $wildcards;
	}


	/**
	 * Add the listeners for the event's interfaces to the given array.
	 *  通过类的接口，实现事件冒泡原理
	 * @param  string  $eventName
	 * @param  array  $listeners
	 * @return array
	 */
	protected function addInterfaceListeners($eventName, array $listeners = [])
	{
		foreach (class_implements($eventName) as $interface) {
			if (isset($this->listeners[$interface])) {
				foreach ($this->listeners[$interface] as $names) {
					$listeners = array_merge($listeners, (array) $names);
				}
			}
		}

		return $listeners;
	}


	/**
	 * @inheritDoc
	 */
	public function push($event, $payload = [])
	{
		// TODO: Implement push() method.
	}

	/**
	 * @inheritDoc
	 */
	public function flush($event)
	{
		// TODO: Implement flush() method.
	}

	/**
	 * @inheritDoc
	 */
	public function forget($event)
	{
		// TODO: Implement forget() method.
	}

	/**
	 * @inheritDoc
	 */
	public function forgetPushed()
	{
		// TODO: Implement forgetPushed() method.
	}
}
