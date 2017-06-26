<?php

namespace Backup\TaskSettings;

use Backup\TaskSettings\TaskSettingsInterface;

class TaskSettings implements TaskSettingsInterface, \Countable {
  /**
   * @var array
   */
  private $collection = [];

  public function __construct(array $collection = [], $taskName = '') {
    $this->collection = $collection;
    $this->collection['name'] = $taskName;
  }


  public function get($name) {
    if ($this->has($name)) {
      return $this->collection[$name];
    }
    else {
      $class = explode('\\', static::class);
      $class = end($class);
      throw new \RuntimeException("Object `$name` does not exist in $class.");
    }
  }

  public function has($name) {
    return array_key_exists($name, $this->collection);
  }

  public function set($name, Cookie $object) {
    $this->collection[$name] = $object;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->collection);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    return $this->has($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    return $this->get($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    $this->set($offset, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    unset($this->collection[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->collection);
  }

  /**
   * @return array
   */
  public function toArray() {
    return iterator_to_array($this);
  }
}