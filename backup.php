#!/usr/bin/env php
<?php
/**
 *
 * backup.php
 *
 * This file is part of backup.php
 * backup-tool.php is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Lesser General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 * backup-tool.php is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for
 * more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Sergio Carracedo Martinez <info@sergiocarracedo.es>
 * @copyright   2016 Sergio Carracedo Martinez
 * @license     http://www.gnu.org/licenses/lgpl-3.0.txt GNU LGPL 3.0
 *
 */

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Backup\Command\RunTasksCommand;
use Pimple\Container;
use \Symfony\Component\Yaml\Yaml;

$app = new Application();

$container = new Container();


//$tasks = Yaml::parse(file_get_contents('/backups/Dropbox/BACKUPS/tasks.yml'));
//$settings = Yaml::parse(file_get_contents(__DIR__ . '/settings.yml'));


$container['notifier'] = function ($c) {
  $notifierClass = $c['settings']['notifier']['class'];
  return new $notifierClass($c);
};

$container['settings'] = Yaml::parse(file_get_contents(__DIR__ . '/settings.yml'));


$command = new RunTasksCommand('run-tasks', $container);
$app->add($command);

$app->setDefaultCommand($command->getName());

$app->run();
