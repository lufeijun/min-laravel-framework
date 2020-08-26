<?php


namespace Lufeijun1234\Log;


use Lufeijun1234\Log\Events\MessageLogged;
use Psr\Log\LoggerInterface;
use Lufeijun1234\Abstracts\Support\Arrayable;
use Lufeijun1234\Abstracts\Support\Jsonable;
use Lufeijun1234\Events\Dispatcher;

class Logger implements LoggerInterface
{

	/**
	 * The underlying logger implementation.
	 *       底层
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * The event dispatcher instance.
	 *
	 * @var \Illuminate\Contracts\Events\Dispatcher|null
	 */
	protected $dispatcher;


	/**
	 * Create a new log writer instance.
	 *
	 * @param  LoggerInterface  $logger
	 * @param  Dispatcher|null  $dispatcher
	 * @return void
	 */
	public function __construct(LoggerInterface $logger, Dispatcher $dispatcher = null)
	{
		$this->logger = $logger;
		$this->dispatcher = $dispatcher;
	}


	/**
	 * Write a message to the log.
	 *
	 * @param  string  $level
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	protected function writeLog($level, $message, $context)
	{
		$this->logger->{$level}($message = $this->formatMessage($message), $context);

		$this->fireLogEvent($level, $message, $context);
	}

	/**
	 * Format the parameters for the logger.
	 *  格式化
	 * @param  mixed  $message
	 * @return mixed
	 */
	protected function formatMessage($message)
	{
		if (is_array($message)) {
			return var_export($message, true);
		} elseif ($message instanceof Jsonable) {
			return $message->toJson();
		} elseif ($message instanceof Arrayable) {
			return var_export($message->toArray(), true);
		}

		return $message;
	}


	/**
	 * Fires a log event.
	 *  触发事件
	 * @param  string  $level
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	protected function fireLogEvent($level, $message, array $context = [])
	{
		// If the event dispatcher is set, we will pass along the parameters to the
		// log listeners. These are useful for building profilers or other tools
		// that aggregate all of the log messages for a given "request" cycle.
		if (isset($this->dispatcher)) {
			$this->dispatcher->dispatch(new MessageLogged($level, $message, $context));
		}
	}


	/**
	 * @inheritDoc
	 */
	public function emergency($message, array $context = array())
	{
		// TODO: Implement emergency() method.
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function alert($message, array $context = array())
	{
		// TODO: Implement alert() method.
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function critical($message, array $context = array())
	{
		// TODO: Implement critical() method.
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function error($message, array $context = array())
	{
		// TODO: Implement error() method.
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function warning($message, array $context = array())
	{
		// TODO: Implement warning() method.
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function notice($message, array $context = array())
	{
		// TODO: Implement notice() method.
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function info($message, array $context = array())
	{
		// TODO: Implement info() method.
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function debug($message, array $context = array())
	{
		// TODO: Implement debug() method.
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function log($level, $message, array $context = array())
	{
		// TODO: Implement log() method.
		$this->writeLog($level, $message, $context);
	}


	/**
	 * Dynamically proxy method calls to the underlying logger.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->logger->{$method}(...$parameters);
	}
}
