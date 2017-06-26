<?php

  namespace Backup\Task;

  use Backup\TaskSettings\TaskSettings;
  use Symfony\Component\Console\Output\OutputInterface;
  use Pimple\Container;
  use Symfony\Component\Process\Exception\ProcessFailedException;
  use Symfony\Component\Process\Process;



  class MD5check extends Task {

    protected $remote;
    protected $localBasePath;
    protected $checksumFile;
    protected $exclude = [];
    protected $remoteConnection;
    protected $remotePath;

    function __construct(Container $container, TaskSettings $taskSettings = null, OutputInterface $output = null) {

      $this->required = array_merge($this->required, ['remote', 'localBasePath', 'checksumFile']);

      parent::__construct($container, $taskSettings, $output);

      list($this->remoteConnection, $this->remotePath) = explode(':', $this->remote);
    }

    function run() {

      $this->output->writeln('Checking files checksum');

      if (!file_exists($this->getChecksumFilename())) {
        $this->output->writeln("<fg=yellow>  Checksum file not not exists. Creating {$this->getChecksumFilename()}</>");
        $this->createChecksum($this->getRemoteChecksums());
      } else {
        //Check checksum
        $this->output->writeln("<fg=blue>  Getting remote checksums</>");
        $rawRemoteChecksums = $this->getRemoteChecksums();
        $remoteChecksums = $this->checksums2Array($rawRemoteChecksums);
        $localChecksums = $this->checksums2Array($this->getLocalChecksums());


        $warnings = [];
        foreach ($localChecksums as $file => $checksum) {
          if (empty($remoteChecksums[$file])) {
            $warnings['missing'][] =  "{$file}";
            $this->output->writeln("<error>   Missing file in remote: {$file}");
          }

          if ($remoteChecksums[$file] != $checksum) {
            $this->output->writeln("<error>   Checksum don't match: {$file}</error>");
            $warnings['checksum'][] = "{$file}";
          }
        }


        foreach ($remoteChecksums as $file => $checksum) {
          if (empty($localChecksums[$file])) {

            $this->output->writeln("<error>   New file detected in remote: {$file}</error>");
            $warnings['new'][] = "{$file}";
          }
        }

        if (empty($warnings)) {
          $this->output->writeln("<info>   Checksum: OK</info>");
        } else {
          $error = 'Missing: ' . count($warnings['missing']);
          $error .= ' - Checksum: ' . count($warnings['checksum']);
          $error .= ' - New: '. count($warnings['new']);
          $error .= ' - View log for more info';
          $this->container['notifier']->send($error, 'md5sum error: '. $this->name, 0);
          $this->renameChecksum();
          $this->createChecksum($rawRemoteChecksums);
        }
      }
    }
    protected function getChecksumFilename() {
      return $this->localBasePath . $this->checksumFile;
    }

    protected function getLocalChecksums() {
      return file_get_contents($this->getChecksumFilename());
    }

    protected function checksums2Array($raw) {
      $lines = explode(PHP_EOL, $raw);
      $checksums = [];
      foreach ($lines as $line) {
        list($md5sum, $path) = explode('  ', $line);
        if (!empty($md5sum)) {
          $checksums[$path] = $md5sum;
        }
      }

      return $checksums;
    }

    protected function getRemoteChecksums() {
      $exclude = '';
      foreach ($this->exclude as $excludePath) {
        $exclude .= ' ! -path "'. $excludePath. '"';
      }

      $remoteCommand  = 'find ' . $this->remotePath . '/* -type f '. $exclude . ' -exec md5sum {} \\;';


      $process = new Process("ssh {$this->remoteConnection} '{$remoteCommand}'");
      $process->setTimeout(3600);
      $process->run();

      if (!$process->isSuccessful()) {
        $this->output->writeln('<error> Error creating checksums ' . print_R($process->getErrorOutput(), true). '</error>');
        $this->container['notifier']->send("Error creating checksums {$this->remotePath}");
        return;
      }

      return $process->getOutput();
    }

    protected function createChecksum($remoteChecksums) {
      $f = fopen($this->getChecksumFilename(), 'w+');
      fwrite($f, $remoteChecksums);
      fclose($f);
    }

    protected function renameChecksum() {
      $checksumFilename = $this->getChecksumFilename();
      rename($checksumFilename, $checksumFilename . '_' . date('Y-m-d'));
    }

  }


