<?php

namespace Backup\Task;

use Backup\Task\Database;
use Backup\TaskSettings\TaskSettings;
use Pimple\Container;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Output\OutputInterface;

class Mysql extends Database {
  protected $rotation = 30;
  protected $port = 3306;
  protected $connectionString = '';

  public function __construct(Container $container, TaskSettings $taskSettings = NULL, OutputInterface $output = null) {
    parent::__construct($container, $taskSettings, $output);
    $this->connectionString = "--port={$this->port} --host={$this->host} --user='{$this->user}' --password='{$this->pass}'";

    $this->rotation = $container['settings']['mysql']['days_rotate'] ?? 30;
  }



  private function getDatabases() {
    $process = new Process("mysql {$this->connectionString} -e'SHOW DATABASES'");

    $process->setTimeout(3600);
    $process->run();


    if (!$process->isSuccessful()) {
      $this->container['notifier']->send('Can\'t get database list', $this->name);
      return false;
      //throw new TaskException('Can\'t get database list', $this->name);
    }

    $databases = explode(PHP_EOL, $process->getOutput());

    $exclude_databases = isset($this->exclude_databases) ? $this->exclude_databases : array();
    return array_filter($databases, function($v) use ($exclude_databases) {
      if (in_array($v, $exclude_databases) || empty($v)) {
        return false;
      }
      return true;
    });
  }

  public function run() {
    $this->output->writeln('Getting database list');
    if (!empty($this->ssh_tunnel)) {
      $processTunnel = new Process("ssh {$this->ssh_tunnel}");
      $processTunnel->setPty(true);
      $processTunnel->start();
      sleep(2);
    }

    $databases = $this->getDatabases();

    foreach ($databases as $dbname) {
      if (!in_array($dbname, array('Database', 'information_schema', ''))) {
        $this->output->writeln(">>> Backing up {$dbname}");

        $backup_filename = $this->local_dir . $this->prefix . $dbname . '-' .  date('Ymd') . '.sql.gz';

        $this->output->writeln("    Backup file {$backup_filename}");
      

        $process = new Process("mysqldump {$this->connectionString} --opt {$dbname} | gzip -9 > {$backup_filename}");
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
          $this->container['notifier']->send("Error running mysqldump for {$backup_filename}");
        }

      }
    }

    if (!empty($this->ssh_tunnel)) {
      $processTunnel->stop(3, SIGINT);
    }


    if (!empty($this->local_dir)) {
      $files = glob($this->local_dir . '/*.sql.gz');
      foreach ($files as $file) {
        if (filemtime($file) < strtotime('-' . $this->rotation . ' DAY')) {
          unlink($file);
        }
      }
    }
  }
}