<?php

namespace Backup\Task;

use Backup\Task\TaskInterface;
use Backup\TaskSettings\TaskSettings;
use Backup\Task\TaskException;
use Pimple\Container;
use Symfony\Component\Console\Output\OutputInterface;

class Task implements TaskInterface {
  protected $name;
  protected $required = ['name'];
  protected $container;
  protected $output;

  public function __construct(Container $container, TaskSettings $taskSettings = NULL, OutputInterface $output = NULL) {
    foreach ($taskSettings as $name => $value) {
      if (isset($value)) {
        $this->{$name} = $value;
      }
      if (!$taskSettings->has($name) || empty($taskSettings[$name])) {
        throw new TaskException($name . ' setting must be set.', $taskSettings->name);
      }
    }

    $this->output = $output;
    $this->container = $container;
  }

  public function run() {

  }
}