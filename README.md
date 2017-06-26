
# Requisites

PHP >=7.0
Composer

# Installation

You need to install dependencies using `composer install`

# Basic configuracion

First you need to create a yml file that list backup taks and a settings.yml file to config, for example the notifier

You can use `settings.example.yml`, just rename to `settings.yml`

I also provide a `tasks.example.yml` in `examples` folder

# Basic usage

`./backup.php run-tasks -T /pathTo/my/tasks.yml`

* settings.yml file must be in same folder backup.php

if yo only nedd run task in a type

`./backup.php run-tasks --type MD5check -T /pathTo/my/tasks.yml`


# Task types

Out the box, we provide abstract tasks:
* Files: Backups files from remote filesystem
* Database: Backup databases

This task are unusable for real task, but other tasks inherits from they.

Implemented tasks are:

* Mysql: Backup MySQL databases
* Rsync: Backup files using rsync
* MD5Check: Create a MD5 hash for every file and compares with previous. Useful to check files integritiy and detect intrusions.

We plan make more tasks, for example: FTP backup for files, PostgreSQL, etc

# Notifiers
Right now, we develop only 2 notifiers: 

* Email: Send email using swiftmailer
* PushOver: Send notification using pushover API (you need to buy a license)

# Settings

Settings are stored in `settings.yml` file. The options are:

`mysql:
  days_to_rotate: XX #Days hold SQL dumps. Older backup files will be erased,

notifier:
  class: \NameSpance\Notifier #Class used to send notificacion. Notifier class MUST implements NotifyInterface
  
  #Push over
  token: [token]
  user: [user]
  
  #email
  to: [to email]
  from: [sender email. Not required]
`




