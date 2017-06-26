<?php

namespace Backup\Notify;
use Backup\Notify\NotifyInterface;
use Pimple\Container;

class Email implements NotifyInterface {
  private $to;
  private $from;

  public function __construct(Container $container) {
    $this->to = $container['settings']['notifier']['to'];
    $this->from = isset($container['settings']['notifier']['from']) ? $container['settings']['notifier']['email']: 'backup@backup.com';
  }

  public function send($message, $title = '', $priority = -1) {

    $body = date('d-m-Y H:i:s');
    $body .= PHP_EOL . $message;

    $message = \Swift_Message::newInstance()
      ->setSubject("[{$priority}] {$title}")
      ->setFrom($this->from)
      ->setTo($this->to)
      ->setBody(
        $body,
        'text/html'
      );


    $transport = new \Swift_MailTransport();
    $mailer = new \Swift_Mailer($transport);

    $result = $mailer->send($message);

  }
}