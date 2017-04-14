<?php
namespace Docopt;



class SingleMatch
{
    /** @var ?int */
    public $pos;

    /** @var Pattern */
    public $pattern;

    /**
     * @param ?int $pos
     * @param Pattern $pattern
     */
    public function __construct($pos, Pattern $pattern=null)
    {
        $this->pos = $pos;
        $this->pattern = $pattern;
    }

    public function toArray() { return array($this->pos, $this->pattern); }
}
