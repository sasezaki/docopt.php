<?php

namespace Docopt;

class Response implements \ArrayAccess, \IteratorAggregate
{
    /** @var int */
    public $status;

    /** @var string */
    public $output;

    /** @var array */
    public $args;

    /**
     * @param array $args
     * @param int $status
     * @param string $output
     */
    public function __construct(array $args, $status=0, $output='')
    {
        $this->args = $args;
        $this->status = $status;
        $this->output = $output;
    }

    public function __get($name)
    {
        if ($name == 'success') {
            return $this->status === 0;
        } else {
            throw new \BadMethodCallException("Unknown property $name");
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->args[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->args[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->args[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->args[$offset]);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->args);
    }
}
