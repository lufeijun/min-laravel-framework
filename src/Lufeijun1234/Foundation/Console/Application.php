<?php


namespace Lufeijun1234\Foundation\Console;

use Lufeijun1234\Console\Command;
use Lufeijun1234\Container\Container;
use Lufeijun1234\Contracts\Console\Application as ApplicationContract;

use Lufeijun1234\Events\Dispatcher;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Input\ArrayInput;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

use Symfony\Component\Console\Exception\CommandNotFoundException;


/**
 * Class Application
 * @package Lufeijun1234\Foundation\Console
 * 应用程序 app，console 专用
 */

class Application extends SymfonyApplication implements ApplicationContract
{
  /**
   * The Laravel application instance.
   */
  protected $laravel;

  /**
   * The output from the previous command.
   *
   * @var \Symfony\Component\Console\Output\BufferedOutput
   */
  protected $lastOutput;

  /**
   * The console application bootstrappers.
   *
   * @var array
   */
  protected static $bootstrappers = [];

  /**
   * The Event Dispatcher.
   *
   * @var \Illuminate\Contracts\Events\Dispatcher
   */
  protected $events;



  public function __construct(Container $laravel, Dispatcher $events, $version)
  {
    // Symfony\Component\Console\Application 类的初始化函数
    parent::__construct('Laravel Framework', $version);


    $this->laravel = $laravel;
    $this->events = $events;
    $this->setAutoExit(false);
    $this->setCatchExceptions(false);

    // 事件先忽略
    // $this->events->dispatch(new ArtisanStarting($this));

    // $this->bootstrap();
  }

  /**
   * Bootstrap the console application.
   *  启动类，
   * @return void
   */
  protected function bootstrap()
  {
    foreach (static::$bootstrappers as $bootstrapper) {
      $bootstrapper($this);
    }
  }



  /**
   * {@inheritdoc}
   * 在 kernel 中直接调用的函数
   * @return int
   */
  public function run(InputInterface $input = null, OutputInterface $output = null)
  {

//    $aa = $this->laravel->make('App\Console\Commands\test');
//
//    $aa->handle();
//    echo "app-run";
//    exit();

    $commandName = $this->getCommandName(
      $input = $input ?: new ArgvInput
    );

//    print_r($input);
//    exit();


    // 事件部分，先忽略
//    $this->events->dispatch(
//      new CommandStarting(
//        $commandName, $input, $output = $output ?: new BufferedConsoleOutput
//      )
//    );

    // 核心运行方法
    $exitCode = parent::run($input, $output);

//    事件部分，先忽略
//    $this->events->dispatch(
//      new CommandFinished($commandName, $input, $output, $exitCode)
//    );

    return $exitCode;
  }


  /**
   * ApplicationContract 定义的接口 start
   */

  /**
   * Run an Artisan console command by name.
   * 调用具体函数
   * @param  string  $command
   * @param  array  $parameters
   * @param  \Symfony\Component\Console\Output\OutputInterface|null  $outputBuffer
   * @return int
   *
   * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
   */
  public function call($command, array $parameters = [], $outputBuffer = null)
  {
    [$command, $input] = $this->parseCommand($command, $parameters);

    if (! $this->has($command)) {
      throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $command));
    }

    return $this->run(
      $input, $this->lastOutput = $outputBuffer ?: new BufferedOutput
    );
  }


  /**
   * Get the output for the last run command.
   *
   * @return string
   */
  public function output()
  {
    return $this->lastOutput && method_exists($this->lastOutput, 'fetch')
      ? $this->lastOutput->fetch()
      : '';
  }


  /**
   * ApplicationContract 定义的接口 end
   */


  /**
   * Parse the incoming Artisan command and its input.
   *
   * @param  string  $command
   * @param  array  $parameters
   * @return array
   */
  protected function parseCommand($command, $parameters)
  {
    if (is_subclass_of($command, SymfonyCommand::class)) {
      $callingClass = true;

      $command = $this->laravel->make($command)->getName();
    }

    if (! isset($callingClass) && empty($parameters)) {
      $command = $this->getCommandName($input = new StringInput($command));
    } else {
      array_unshift($parameters, $command);

      $input = new ArrayInput($parameters);
    }

    return [$command, $input];
  }


  /**
   * Register a console "starting" bootstrapper.
   * 注册
   * @param  \Closure  $callback
   * @return void
   */
  public static function starting(\Closure $callback)
  {
    static::$bootstrappers[] = $callback;
  }


  /**
   * Add a command, resolving through the application.
   * 添加一个命令，通过 app 解析对应的类
   * @param  string  $command
   * @return \Symfony\Component\Console\Command\Command
   */
  public function resolve($command)
  {
    return $this->add($this->laravel->make($command));
  }

  /**
   * Add a command to the console.
   *
   * @param  \Symfony\Component\Console\Command\Command  $command
   * @return \Symfony\Component\Console\Command\Command
   */
  public function add(SymfonyCommand $command)
  {
    if ($command instanceof Command) {
      $command->setLaravel($this->laravel);
    }

    return $this->addToParent($command);
  }

  /**
   * Add the command to the parent instance.
   *
   * @param  \Symfony\Component\Console\Command\Command  $command
   * @return \Symfony\Component\Console\Command\Command
   */
  protected function addToParent(SymfonyCommand $command)
  {
    return parent::add($command);
  }



  /**
   * Resolve an array of commands through the application.
   * 解析命令
   * @param  array|mixed  $commands
   * @return $this
   */
  public function resolveCommands($commands)
  {
    $commands = is_array($commands) ? $commands : func_get_args();

    foreach ($commands as $command) {
      $this->resolve($command);
    }

    return $this;
  }

}