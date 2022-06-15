<?php

namespace Lufeijun1234\Console;
use Lufeijun1234\Traits\Macroable;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends SymfonyCommand
{
//  use Concerns\CallsCommands,
//    Concerns\HasParameters,
//    Concerns\InteractsWithIO,
//    Macroable;
  use Macroable;



  /**
   * Set the Laravel application instance.
   *
   * @param  \Illuminate\Contracts\Container\Container  $laravel
   * @return void
   */
  public function setLaravel($laravel)
  {
    $this->laravel = $laravel;
  }

  /**
   * Run the console command.
   *
   * @param  \Symfony\Component\Console\Input\InputInterface  $input
   * @param  \Symfony\Component\Console\Output\OutputInterface  $output
   * @return int
   */
  public function run(InputInterface $input, OutputInterface $output)
  {
    echo "command--run";
    exit();
//    $this->output = $this->laravel->make(
//      OutputStyle::class, ['input' => $input, 'output' => $output]
//    );
//
//    return parent::run(
//      $this->input = $input, $this->output
//    );
  }

  /**
   * Execute the console command.
   *
   * @param  \Symfony\Component\Console\Input\InputInterface  $input
   * @param  \Symfony\Component\Console\Output\OutputInterface  $output
   * @return int
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    echo "command execute";
    exit();
    $method = method_exists($this, 'handle') ? 'handle' : '__invoke';

    return (int) $this->laravel->call([$this, $method]);
  }

  /**
   * Resolve the console command instance for the given command.
   *  通过给定的命令解析对应的类
   * @param  \Symfony\Component\Console\Command\Command|string  $command
   * @return \Symfony\Component\Console\Command\Command
   */
  protected function resolveCommand($command)
  {

    echo "resolveCommand";

    exit();
    if (! class_exists($command)) {
      return $this->getApplication()->find($command);
    }

    $command = $this->laravel->make($command);

    if ($command instanceof SymfonyCommand) {
      $command->setApplication($this->getApplication());
    }

    if ($command instanceof self) {
      $command->setLaravel($this->getLaravel());
    }

    return $command;
  }


}