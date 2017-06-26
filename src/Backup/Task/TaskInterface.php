<?php

namespace Backup\Task;

use Backup\TaskSettings\TaskSettings;
use Pimple\Container;
use Symfony\Component\Console\Output\OutputInterface;

interface TaskInterface {
    public function __construct(Container $container, TaskSettings $taskSettings = null, OutputInterface $output = null);

    public function run();
}