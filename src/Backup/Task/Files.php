<?php

namespace Backup\Task;

use Backup\TaskSettings\TaskSettings;
use Backup\Task\Task;
use Pimple\Container;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;


class Files extends Task {

  protected $localBasePath;
  protected $local;
  protected $remote;
  protected $exclude;
  protected $useGit = TRUE;
  protected $keepGitCommits = 10;


  public function __construct(Container $container, TaskSettings $taskSettings = NULL, OutputInterface $output = NULL) {
    $this->required = $this->required + ['remote', 'local'];

    parent::__construct($container, $taskSettings, $output);

    $this->local = rtrim($this->localBasePath, '/') . '/' . $this->local;
  }

  public function run() {
    if ($this->useGit) {
      $this->gitInit();
      $this->gitAddAndCommit();
      $this->gitRebase();
    }
  }

  protected function gitInit() {
    $process = new Process("cd {$this->local}; git init");
    $process->run();
  }

  protected function gitAddAndCommit() {
    $date = date('Y-m-d H:i:s');
    $process = new Process("cd {$this->local}; git add -A; git commit -m'{$date}'");
    $process->setTimeout(4 * 60 * 60);
    $process->run();

    if (!$process->isSuccessful()) {
      $this->container['notifier']->send($process->getErrorOutput(), 'Error rebasing');
    }

  }


  protected function gitRebase() {
    $rebase = 'cd ' . $this->local . '; b="$(git branch --no-color | cut -c3-)" ; h="$(git rev-parse $b)" ; echo "Current branch: $b $h" ; c="$(git rev-parse $b~' . ($this->keepGitCommits - 1) . ')" ; echo "Recreating $b branch with initial commit $c ..." ; git checkout --orphan new-start $c ; git commit -C $c ; git rebase --onto new-start $c $b ; git branch -d new-start ; git gc';
    $process = new Process($rebase);
    $process->setTimeout(4 * 60 * 60);
    $process->run();

    // executes after the command finishes
    if (!$process->isSuccessful()) {
      $this->container['notifier']->send($process->getErrorOutput(), 'Error rebasing ' . $this->local);
    }
  }
}
