<?php


namespace Lufeijun1234\Foundation\Console\Scheduling;


use Lufeijun1234\Container\Container;
use Lufeijun1234\Traits\Macroable;


/**
 * Class Schedule
 * @package Lufeijun1234\Foundation\Console\Scheduling
 * 调度器
 */
class Schedule
{
  use Macroable;

  const SUNDAY = 0;
  const MONDAY = 1;
  const TUESDAY = 2;
  const WEDNESDAY = 3;
  const THURSDAY = 4;
  const FRIDAY = 5;
  const SATURDAY = 6;

  /**
   * All of the events on the schedule.
   *
   * @var \Illuminate\Console\Scheduling\Event[]
   */
  protected $events = [];

  /**
   * The event mutex implementation.
   *
   * @var \Illuminate\Console\Scheduling\EventMutex
   */
  protected $eventMutex;

  /**
   * The scheduling mutex implementation.
   *
   * @var \Illuminate\Console\Scheduling\SchedulingMutex
   */
  protected $schedulingMutex;

  /**
   * The timezone the date should be evaluated on.
   *
   * @var \DateTimeZone|string
   */
  protected $timezone;

  /**
   * The job dispatcher implementation.
   *
   * @var \Illuminate\Contracts\Bus\Dispatcher
   */
  protected $dispatcher;

  /**
   * Create a new schedule instance.
   *
   * @param  \DateTimeZone|string|null  $timezone
   * @return void
   *
   * @throws \RuntimeException
   */
  public function __construct($timezone = null)
  {
    $this->timezone = $timezone;

    if (! class_exists(Container::class)) {
      throw new \RuntimeException(
        'A container implementation is required to use the scheduler. Please install the illuminate/container package.'
      );
    }

//    $container = Container::getInstance();


    // 这两个事件暂时放弃
//    $this->eventMutex = $container->bound(EventMutex::class)
//      ? $container->make(EventMutex::class)
//      : $container->make(CacheEventMutex::class);
//
//    $this->schedulingMutex = $container->bound(SchedulingMutex::class)
//      ? $container->make(SchedulingMutex::class)
//      : $container->make(CacheSchedulingMutex::class);
  }



}