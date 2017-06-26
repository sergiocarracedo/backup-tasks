<?php

namespace Backup\Task;

use Backup\Task\Task;
use Backup\TaskSettings\TaskSettings;
use Backup\Task\TaskException;
use Pimple\Container;
use Symfony\Component\Console\Output\OutputInterface;

class Database extends Task {
  protected $local_dir = '/backups/hostings/mysql';
  protected $prefix;
  protected $user;
  protected $pass;
  protected $host;
  protected $port;
  protected $exclude_databases;
  protected $ssh_tunnel;

  public function __construct(Container $container, TaskSettings $taskSettings = NULL, OutputInterface $output = NULL) {
    $this->required = $this->required + ['user', 'pass', 'host'];

    parent::__construct($container, $taskSettings, $output);

    $this->local_dir = rtrim($this->local_dir, '/') . '/';
  }

  public function run() {

  }
}