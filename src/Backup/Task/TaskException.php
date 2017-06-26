<?php
namespace Backup\Task;

use Backup\Notify\PushOver;

class TaskException extends \Exception {


  public function __construct($message, $taskName, $code = 0, \Exception $previous = null) {
    $notify = new PushOver();
    $notify->send($message, $taskName, 1);

    parent::__construct($message, $code, $previous);
  }
}