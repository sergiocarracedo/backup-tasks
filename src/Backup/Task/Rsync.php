<?php
namespace Backup\Task;

use \Backup\TaskSettings\TaskSettings;
use Pimple\Container;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Output\OutputInterface;

class Rsync extends Files {

  public function __construct(Container $container, TaskSettings $taskSettings = null, OutputInterface $output = null) {
    parent::__construct($container, $taskSettings, $output);
  }


  public function run() {

    $this->output->writeln("Rsync {$this->name}: {$this->remote}");

    if (!empty($this->exclude)) {
      $this->output->writeln('>>> Excluding: ' . implode(', ', $this->exclude));
    }
    $this->output->writeln(">>> Target: {$this->local}");

    if (!is_dir($this->local) && !file_exists($this->local)) {
      $this->output->writeln("<fg=yellow>  Target not exists. Trying mkdir {$this->local}</>");
      try {
        mkdir($this->local);
      } catch (Exception $e) {
        $this->output->writeln("<error>  mkdir fails!, check permissions: {$this->local}</>");
        return false;
      }
    }

    $rsyncCommand = 'rsync -rav';
    if (!empty($this->exclude)) {
      foreach ($this->exclude as $exclude) {
        $rsyncCommand .= ' --exclude=\'' . $exclude . '\'';
      }
    }
    $rsyncCommand .= ' --cvs-exclude --delete';
    $rsyncCommand .= " {$this->remote}";
    $rsyncCommand .= " {$this->local}";
    $process = new Process($rsyncCommand);
    $process->setTimeout(4 * 60 * 60);
    $process->run(function ($type, $buffer) {
      if (Process::ERR === $type) {
        echo 'ERR > '.$buffer;
      } else {
        echo '    > '.$buffer;
      }
    });

    // executes after the command finishes
    if (!$process->isSuccessful()) {
      $this->container['notifier']->send($process->getErrorOutput(), 'Rsync error');
    }
	parent::run();
  }
}
