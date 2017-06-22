<?php

namespace Docopt;

class Tokens extends \ArrayIterator
{
    /** @var string */
    public $error;

    /**
     * @param array|string $source
     * @param string $error Class name of error exception
     */
    public function __construct($source, $error='ExitException')
    {
        if (!is_array($source)) {
            $source = trim($source);
            if ($source) {
                $source = preg_split('/\s+/', $source);
            } else {
                $source = array();
            }
        }

        parent::__construct($source);

        $this->error = $error;
    }

    /**
     * @param string $source
     * @return self
     */
    public static function fromPattern($source)
    {
        $source = preg_replace('@([\[\]\(\)\|]|\.\.\.)@', ' $1 ', $source);
        $source = preg_split('@\s+|(\S*<.*?'.'>)@', $source, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        return new static($source, 'LanguageError');
    }

    /**
     * @return string
     */
    function move()
    {
        $item = $this->current();
        $this->next();
        return $item;
    }

    /**
     * @return string[]
     */
    function left()
    {
        $left = array();
        while (($token = $this->move()) !== null) {
            $left[] = $token;
        }
        return $left;
    }

    /**
     * @param string $message
     */
    function raiseException($message)
    {
        $class = __NAMESPACE__.'\\'.$this->error;
        throw new $class($message);
    }
}
