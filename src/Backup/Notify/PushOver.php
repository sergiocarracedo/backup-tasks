<?php

  namespace Backup\Notify;
  use Backup\Notify\NotifyInterface;
  use Pimple\Container;

  class PushOver implements NotifyInterface {
    private $token;
    private $user;

    public function __construct(Container $container) {
      $this->token = $container['settings']['notifier']['token'];
      $this->user = $container['settings']['notifier']['user'];
    }

    public function send($message, $title = '', $priority = -1) {
      $ch = curl_init();
      curl_setopt_array($ch, array(
        CURLOPT_URL => "https://api.pushover.net/1/messages.json",
        CURLOPT_POSTFIELDS => array(
          "token" => $this->token,
          "user" => $this->user,
          "message" => $message,
          'title' => $title,
          'priority' => (string)$priority,
        ),
        CURLOPT_SAFE_UPLOAD => true,
        CURLOPT_RETURNTRANSFER => true
      ));
      $response = curl_exec($ch);
      curl_close($ch);
    }
  }