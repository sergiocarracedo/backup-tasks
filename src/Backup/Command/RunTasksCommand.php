<?php
namespace Backup\Command;

use Backup\TaskSettings\TaskSettings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\LockableTrait;
use Backup\Notify\PushOver;
use Symfony\Component\Yaml\Yaml;

class RunTasksCommand extends Command {

  use LockableTrait;

  private $tasks;
  protected $container;
  private $stats = [];

  public function __construct($name, $container) {
    $this->container = $container;
    parent::__construct($name);
  }

  protected function configure() {
    $this
      ->setDescription('Execute backup tasks')
      ->setHelp('This command execute backup tasks')
      ->setDefinition(
        new InputDefinition(array(
          new InputOption('exclude', 'e', InputOption::VALUE_OPTIONAL),
          new InputOption('type', 't', InputOption::VALUE_OPTIONAL),
          new InputOption('tasks', 'T', InputOption::VALUE_REQUIRED),
        ))
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    $taskFile = $input->getOption('tasks');
    if (!file_exists($taskFile)) {
      throw new \Exception('Tasks file <b>'.$taskFile.'<b> not found');
    }

    $tasks = self::parseYaml($taskFile);
    $this->container['tasks'] = $tasks;

    if (!$this->lock()) {
      $output->writeln('The command is already running in another process.');
      return 0;
    }

    $output->writeln('<info>Running tasks.....</info>');
    $taskType = $input->getOption('type');

    $this->container['notifier']->send('Starting....', 'Backup tasks '. $taskType);

    if (!empty($taskType)) {
      $output->writeln('   Running only type: ' . $taskType);
    }

    foreach($this->container['tasks'] as $taskName => $taskSettings) {

      if (empty($taskType) || $taskSettings['type'] == $taskType) {
        $taskClass = 'Backup\\Task\\' . $taskSettings['type'];


        if (class_exists($taskClass)) {
          $output->writeln('   Starting task: ' . $taskName);
          $startTime = microtime(true);
          $output->writeln('<info>Running task <fg=white>' . $taskName . '</> (' . $taskSettings['type'] . ')</info>');
          $task = new $taskClass($this->container, new TaskSettings($taskSettings, $taskName), $output);
          $task->run();
          $elapsedTime = microtime(true) - $startTime;

          $this->stats[$taskSettings['type']] = !isset($this->stats[$taskSettings['type']]) ? $elapsedTime : $this->stats[$taskSettings['type']] + $elapsedTime;
          $this->stats['total'] = !isset($this->stats['total']) ? $elapsedTime : $this->stats['total'] + $elapsedTime;

          $output->writeln('      <info>Time elapsed: ' . number_format($elapsedTime, 2)  . 's</info>');
          $output->writeln('      Task finished');
        }
      }
    }

    $statsString = PHP_EOL;
    foreach ($this->stats as $name => $elapsed) {
      $elapsed = number_format($elapsed, 2);
      $statsString .= "{$name}: {$elapsed}s" . PHP_EOL;
    }

    $output->writeln('<info>Total Time elapsed: ' . number_format($this->stats['total'], 2)  . 's</info>');


    $this->container['notifier']->send("All tasks ran ok" . $statsString, 'Backup tasks');

    $this->release();
  }


  public static function parseYaml($ymlFile, Array $defaults = []) {
    $ymlContent = file_get_contents($ymlFile);

    $parsed = Yaml::parse($ymlContent);
    $tasks = [];

    foreach($parsed as $name => $item) {

      $taskDefaults = array_map(function($item) use ($name) {
        return str_replace('[task-name]', $name, $item);
      }, $defaults);

      if ($name == 'imports') {
        foreach ($item as $import) {
          $importDefaults = isset($import['defaults']) ? $import['defaults'] : [];
          $importFile = realpath(dirname($ymlFile). '/'. $import['resource']);

          $tasks = $tasks + self::parseYaml($importFile, $importDefaults);
        }
      } else {
        $tasks[$name] = array_merge($taskDefaults, $item);
      }
    }

    return $tasks;
  }

}
