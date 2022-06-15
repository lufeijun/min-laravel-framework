<?php


namespace Lufeijun1234\Contracts\Console;

/**
 * Interface Application
 * @package Lufeijun1234\Contracts\Console
 * 专为 console 设计的 app 类
 */
interface Application
{
  /**
   * Run an Artisan console command by name.
   *
   * @param  string  $command
   * @param  array  $parameters
   * @param  \Symfony\Component\Console\Output\OutputInterface|null  $outputBuffer
   * @return int
   */
  public function call($command, array $parameters = [], $outputBuffer = null);

  /**
   * Get the output from the last command.
   *
   * @return string
   */
  public function output();
}