<?php

namespace Backup\TaskSettings;

interface TaskSettingsInterface extends \IteratorAggregate, \ArrayAccess{
    /**
     * @param string $name
     * @return mixed
     */
    public function get($name);
    /**
     * @param string $name
     * @param mixed $object
     */
    public function set($name, Cookie $object);
    /**
     * @param string $name
     * @return mixed
     */
    public function has($name);
}