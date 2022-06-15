<?php

namespace Lufeijun1234\Foundation\Console;

use Lufeijun1234\Console\Command;
use Lufeijun1234\Contracts\Console\Kernel as KernelContract;
use Lufeijun1234\Contracts\Debug\ExceptionHandler;
use Lufeijun1234\Events\Dispatcher;
use Lufeijun1234\Foundation\Application;
use Lufeijun1234\Foundation\Console\Scheduling\Schedule;

use Lufeijun1234\Foundation\Console\Application as Artisan;

use Lufeijun1234\Support\Arr;
use Lufeijun1234\Support\Str;
use Throwable,ReflectionClass;

use Symfony\Component\Finder\Finder;

/**
 * 
 */
class Kernel implements KernelContract
{

  /**
   * The application implementation.
   *
   * @var Lufeijun1234\Foundation\Application
   */
  protected $app;

  /**
   * The event dispatcher implementation.
   *  事件部分，也可以不用
   * @var \Illuminate\Contracts\Events\Dispatcher
   */
  protected $events;

  /**
   * The Artisan application instance.
   *
   * @var Lufeijun1234\Foundation\Application|null
   */
  protected $artisan;

  /**
   * The Artisan commands provided by the application.
   *
   * @var array
   */
  protected $commands = [];

  /**
   * Indicates if the Closure commands have been loaded.
   *
   * @var bool
   */
  protected $commandsLoaded = false;


  /**
   * The bootstrap classes for the application.
   *
   * @var string[]
   */
  protected $bootstrappers = [
    \Lufeijun1234\Foundation\Bootstrap\LoadEnvironmentVariables::class,
    \Lufeijun1234\Foundation\Bootstrap\LoadConfiguration::class,
    \Lufeijun1234\Foundation\Bootstrap\HandleExceptions::class,
    \Lufeijun1234\Foundation\Bootstrap\RegisterFacades::class,
    \Lufeijun1234\Foundation\Bootstrap\RegisterProviders::class,
    \Lufeijun1234\Foundation\Bootstrap\BootProviders::class,
  ];



  /**
   * Create a new console kernel instance.
   *
   * @param  \Illuminate\Contracts\Foundation\Application  $app
   * @param  \Illuminate\Contracts\Events\Dispatcher  $events
   * @return void
   */
  public function __construct(Application $app, Dispatcher $events)
  {
    if (! defined('ARTISAN_BINARY')) {
      define('ARTISAN_BINARY', 'artisan');
    }

    $this->app = $app;
    $this->events = $events;

    $this->app->booted(function () {
      $this->defineConsoleSchedule();
    });
  }


  /**
   * Define the application's command schedule.
   *
   * @return void
   */
  protected function defineConsoleSchedule()
  {
    $this->app->singleton(Schedule::class, function ($app) {
      return tap(new Schedule($this->scheduleTimezone()), function ($schedule) {
        $this->schedule($schedule->useCache($this->scheduleCache()));
      });
    });
  }


  /**
   * Get the timezone that should be used by default for scheduled events.
   *
   * @return \DateTimeZone|string|null
   */
  protected function scheduleTimezone()
  {
    $config = $this->app['config'];

    return $config->get('app.schedule_timezone', $config->get('app.timezone'));
  }




  /**
   * KernelContract 类的继承接口 start
   */


  /**
   * Bootstrap the application for artisan commands.
   *
   * @return void
   */
  public function bootstrap()
  {
    if (! $this->app->hasBeenBootstrapped()) {
      $this->app->bootstrapWith($this->bootstrappers());
    }

    $this->app->loadDeferredProviders();

    if (! $this->commandsLoaded) {
      $this->commands();

      $this->commandsLoaded = true;
    }
  }


  /**
   * Run the console application.
   * 实际处理入口
   * @param  \Symfony\Component\Console\Input\InputInterface  $input
   * @param  \Symfony\Component\Console\Output\OutputInterface|null  $output
   * @return int
   */
  public function handle($input, $output = null)
  {
    try {
      $this->bootstrap();
      return $this->getArtisan()->run($input, $output);
    } catch (Throwable $e) {
      // 异常的处理

      echo "error\n";
      throw $e;

      $this->reportException($e);

      $this->renderException($output, $e);

      return 1;
    }
  }

  /**
   * Run an Artisan console command by name.
   *
   * @param  string  $command
   * @param  array  $parameters
   * @param  \Symfony\Component\Console\Output\OutputInterface|null  $outputBuffer
   * @return int
   *
   * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
   */
  public function call($command, array $parameters = [], $outputBuffer = null)
  {
    $this->bootstrap();

    return $this->getArtisan()->call($command, $parameters, $outputBuffer);
  }

  /**
   * Queue the given console command.
   * 队列部分，暂时先忽略
   * @param  string  $command
   * @param  array  $parameters
   * @return \Illuminate\Foundation\Bus\PendingDispatch
   */
  public function queue($command, array $parameters = [])
  {
    // return QueuedCommand::dispatch(func_get_args());
  }

  /**
   * Get all of the commands registered with the console.
   * 获取所有命令
   * @return array
   */
  public function all()
  {
    $this->bootstrap();

    return $this->getArtisan()->all();
  }

  /**
   * Get the output for the last run command.
   *
   * @return string
   */
  public function output()
  {
    $this->bootstrap();

    return $this->getArtisan()->output();
  }

  /**
   * Terminate the application.
   *
   * @param  \Symfony\Component\Console\Input\InputInterface  $input
   * @param  int  $status
   * @return void
   */
  public function terminate($input, $status)
  {
    $this->app->terminate();
  }




  /**
   * KernelContract 类的继承接口 end
   */


  /**
   * Get the bootstrap classes for the application.
   *  需要启动的包
   * @return array
   */
  protected function bootstrappers()
  {
    return $this->bootstrappers;
  }

  /**
   * Register the Closure based commands for the application.
   *  空的函数体，
   * @return void
   */
  protected function commands()
  {
    //
  }

  /**
   * Report the exception to the exception handler.
   *
   * @param  \Throwable  $e
   * @return void
   */
  protected function reportException(Throwable $e)
  {
    $this->app[ExceptionHandler::class]->report($e);
  }

  /**
   * Render the given exception.
   *
   * @param  \Symfony\Component\Console\Output\OutputInterface  $output
   * @param  \Throwable  $e
   * @return void
   */
  protected function renderException($output, Throwable $e)
  {
    $this->app[ExceptionHandler::class]->renderForConsole($output, $e);
  }



  /**
   * Get the Artisan application instance.
   *
   * @return \Illuminate\Console\Application
   */
  protected function getArtisan()
  {
    if (is_null($this->artisan)) {
      return $this->artisan = (new Artisan($this->app, $this->events, $this->app->version()))
        ->resolveCommands($this->commands);
    }

    return $this->artisan;
  }


  /**
   * Register all of the commands in the given directory.
   * 加载所有命令
   * @param  array|string  $paths
   * @return void
   */
  protected function load($paths)
  {
    $paths = array_unique(Arr::wrap($paths));

    $paths = array_filter($paths, function ($path) {
      return is_dir($path);
    });


    if (empty($paths)) {
      return;
    }

    $namespace = $this->app->getNamespace();

    foreach ((new Finder)->in($paths)->files() as $command) {
      $command = $namespace.str_replace(
          ['/', '.php'],
          ['\\', ''],
          Str::after($command->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
        );
      if (is_subclass_of($command, Command::class) &&
        ! (new ReflectionClass($command))->isAbstract()) {
        Artisan::starting(function ($artisan) use ($command) {
          $artisan->resolve($command);
        });
      }
    }
  }

}