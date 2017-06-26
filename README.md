# backup-task

# Requisites

PHP >=7.0
Composer

You need to install dependencies using
`composer install`


# Basic configuracion

First you need to create a yml file that list backup taks and a settings.yml file to config, for example the notifier

You can use `settings.example.yml`, just rename to `settings.yml`

I also provide a `tasks.example.yml` in `examples` folder

# Basic usage

`./backup.php run-tasks -T /pathTo/my/tasks.yml`

* settings.yml file must be in same folder backup.php

if yo only nedd run task in a type

`./backup.php run-tasks --type MD5check -T /pathTo/my/tasks.yml`







