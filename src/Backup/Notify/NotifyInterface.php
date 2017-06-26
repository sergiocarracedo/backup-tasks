<?php

namespace Backup\Notify;
use Pimple\Container;

interface NotifyInterface {
  function __construct(Container $container);

  function send($message, $title = '', $priority = -1);
}