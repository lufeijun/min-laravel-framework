<?php


namespace Lufeijun1234\Log;


use InvalidArgumentException;
use Lufeijun1234\Support\Str;
use Psr\Log\LoggerInterface;
use Lufeijun1234\Container\Container;
use Throwable;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Logger as Monolog;



class LogManager implements LoggerInterface
{

	use ParsesLogConfiguration;

	/**
	 * The application instance.
	 *   框架
	 * @var Container
	 */
	protected $app;

	/**
	 * The array of resolved channels.
	 *
	 * @var array
	 */
	protected $channels = [];

	/**
	 * The registered custom driver creators.
	 *  自定义
	 * @var array
	 */
	protected $customCreators = [];

	/**
	 * The standard date format to use when writing logs.
	 *
	 * @var string
	 */
	protected $dateFormat = 'Y-m-d H:i:s';

	/**
	 * Create a new Log manager instance.
	 *
	 * @param  Container  $app
	 * @return void
	 */
	public function __construct(Container $app)
	{
		$this->app = $app;
	}


	/**
	 * Get a log driver instance.
	 *  获取驱动
	 * @param  string|null  $driver
	 * @return LoggerInterface
	 */
	public function driver($driver = null)
	{
		return $this->get($driver ?? $this->getDefaultDriver());
	}

	/**
	 * Get the default log driver name.
	 *  获取默认值
	 * @return string
	 */
	public function getDefaultDriver()
	{
		return $this->app['config']['logging.default'];
	}


	/**
	 * Attempt to get the log from the local cache.
	 *
	 * @param  string  $name
	 * @return LoggerInterface
	 */
	protected function get($name)
	{
		try {
			return $this->channels[$name] ?? with($this->resolve($name), function ($logger) use ($name) {
					return $this->channels[$name] = $this->tap($name, new Logger($logger, $this->app['events']));
				});
		} catch (Throwable $e) {
			return tap($this->createEmergencyLogger(), function ($logger) use ($e) {
				$logger->emergency('Unable to create configured logger. Using emergency logger.', [
					'exception' => $e,
				]);
			});
		}
	}

	/**
	 * Apply the configured taps for the logger.
	 *
	 * @param  string  $name
	 * @param Logger  $logger
	 * @return Logger
	 */
	protected function tap($name, Logger $logger)
	{
		foreach ($this->configurationFor($name)['tap'] ?? [] as $tap) {
			[$class, $arguments] = $this->parseTap($tap);

			$this->app->make($class)->__invoke($logger, ...explode(',', $arguments));
		}

		return $logger;
	}

	/**
	 * Parse the given tap class string into a class name and arguments string.
	 *
	 * @param  string  $tap
	 * @return array
	 */
	protected function parseTap($tap)
	{
		return Str::contains($tap, ':') ? explode(':', $tap, 2) : [$tap, ''];
	}


	/**
	 * Resolve the given log instance by name.
	 *  解析实现类
	 * @param  string  $name
	 * @return LoggerInterface
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function resolve($name)
	{
		$config = $this->configurationFor($name);
		// 检测是否有配置文件
		if (is_null($config)) {
			throw new InvalidArgumentException("Log [{$name}] is not defined.");
		}

		// 是否自定义实现
		if (isset($this->customCreators[$config['driver']])) {
			return $this->callCustomCreator($config);
		}

		// 拼接驱动函数名称
		$driverMethod = 'create'.ucfirst($config['driver']).'Driver';

		if (method_exists($this, $driverMethod)) {
			return $this->{$driverMethod}($config);
		}

		throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
	}


	/**
	 * Create an aggregate log driver instance.
	 *
	 * @param  array  $config
	 * @return \Psr\Log\LoggerInterface
	 */
	protected function createStackDriver(array $config)
	{
//		$handlers = collect($config['channels'])->flatMap(function ($channel) {
//			return $this->channel($channel)->getHandlers();
//		})->all();

		$handlers = [];

		foreach ( $config['channels'] as $channel )
		{
			$handlers = $this->channel($channel)->getHandlers();
		}

		if ($config['ignore_exceptions'] ?? false) {
			$handlers = [new WhatFailureGroupHandler($handlers)];
		}

		return new Monolog($this->parseChannel($config), $handlers);
	}


	/**
	 * Create an instance of the single file log driver.
	 *
	 * @param  array  $config
	 * @return \Psr\Log\LoggerInterface
	 */
	protected function createSingleDriver(array $config)
	{
		return new Monolog($this->parseChannel($config), [
			$this->prepareHandler(
				new StreamHandler(
					$config['path'], $this->level($config),
					$config['bubble'] ?? true, $config['permission'] ?? null, $config['locking'] ?? false
				), $config
			),
		]);
	}


	/**
	 * Create an instance of the daily file log driver.
	 *
	 * @param  array  $config
	 * @return \Psr\Log\LoggerInterface
	 */
	protected function createDailyDriver(array $config)
	{
		return new Monolog($this->parseChannel($config), [
			$this->prepareHandler(new RotatingFileHandler(
				$config['path'], $config['days'] ?? 7, $this->level($config),
				$config['bubble'] ?? true, $config['permission'] ?? null, $config['locking'] ?? false
			), $config),
		]);
	}



	/**
	 * Get a log channel instance.
	 *
	 * @param  string|null  $channel
	 * @return \Psr\Log\LoggerInterface
	 */
	public function channel($channel = null)
	{
		return $this->driver($channel);
	}




	/**
	 * Get the log connection configuration.
	 *  获取日志配置
	 * @param  string  $name
	 * @return array
	 */
	protected function configurationFor($name)
	{
		return $this->app['config']["logging.channels.{$name}"];
	}

	/**
	 * Call a custom driver creator.
	 *  用户自定义
	 * @param  array  $config
	 * @return mixed
	 */
	protected function callCustomCreator(array $config)
	{
		return $this->customCreators[$config['driver']]($this->app, $config);
	}


	/**
	 * Create an emergency log handler to avoid white screens of death.
	 *
	 * @return LoggerInterface
	 */
	protected function createEmergencyLogger()
	{
		$config = $this->configurationFor('emergency');

		$handler = new StreamHandler(
			$config['path'] ?? $this->app->storagePath().'/logs/laravel.log',
			$this->level(['level' => 'debug'])
		);

		return new Logger(
			new Monolog('laravel', $this->prepareHandlers([$handler])),
			$this->app['events']
		);
	}


	/**
	 * Prepare the handlers for usage by Monolog.
	 *
	 * @param  array  $handlers
	 * @return array
	 */
	protected function prepareHandlers(array $handlers)
	{
		foreach ($handlers as $key => $handler) {
			$handlers[$key] = $this->prepareHandler($handler);
		}

		return $handlers;
	}

	/**
	 * Prepare the handler for usage by Monolog.
	 *
	 * @param HandlerInterface $handler
	 * @param  array  $config
	 * @return HandlerInterface
	 */
	protected function prepareHandler(HandlerInterface $handler, array $config = [])
	{
		$isHandlerFormattable = false;

		if (Monolog::API === 1) {
			$isHandlerFormattable = true;
		} elseif (Monolog::API === 2 && $handler instanceof FormattableHandlerInterface) {
			$isHandlerFormattable = true;
		}

		if ($isHandlerFormattable && ! isset($config['formatter'])) {
			$handler->setFormatter($this->formatter());
		} elseif ($isHandlerFormattable && $config['formatter'] !== 'default') {
			$handler->setFormatter($this->app->make($config['formatter'], $config['formatter_with'] ?? []));
		}

		return $handler;
	}

	/**
	 * Get a Monolog formatter instance.
	 *  获取 Monolog 格式化
	 * @return \Monolog\Formatter\FormatterInterface
	 */
	protected function formatter()
	{
		return tap(new LineFormatter(null, $this->dateFormat, true, true), function ($formatter) {
			$formatter->includeStacktraces();
		});
	}



	//// 接口函数

	/**
	 * @inheritDoc
	 */
	public function emergency($message, array $context = array())
	{
		// TODO: Implement emergency() method.
		$this->driver()->emergency($message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function alert($message, array $context = array())
	{
		// TODO: Implement alert() method.
		$this->driver()->alert($message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function critical($message, array $context = array())
	{
		// TODO: Implement critical() method.
		$this->driver()->critical($message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function error($message, array $context = array())
	{
		// TODO: Implement error() method.
		$this->driver()->error($message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function warning($message, array $context = array())
	{
		// TODO: Implement warning() method.
		$this->driver()->warning($message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function notice($message, array $context = array())
	{
		// TODO: Implement notice() method.
		$this->driver()->notice($message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function info($message, array $context = array())
	{
		// TODO: Implement info() method.
		$this->driver()->info($message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function debug($message, array $context = array())
	{
		// TODO: Implement debug() method.
		$this->driver()->debug($message, $context);
	}

	/**
	 * @inheritDoc
	 */
	public function log($level, $message, array $context = array())
	{
		// TODO: Implement log() method.
		$this->driver()->log($level, $message, $context);
	}

	/**
	 * @inheritDoc
	 */
	protected function getFallbackChannelName()
	{
		// TODO: Implement getFallbackChannelName() method.
		return $this->app->bound('env') ? $this->app->environment() : 'production';
	}
}
