<?php
namespace Docopt;

class Command extends Argument
{
    /** @var string */
    public $name;

    public $value;

    /**
     * @param string $name
     * @param bool $value
     */
    public function __construct($name, $value=false)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @param Pattern[] $left
     * @return SingleMatch
     */
    function singleMatch($left)
    {
        foreach ($left as $n=>$pattern) {
            if ($pattern instanceof Argument) {
                if ($pattern->value == $this->name) {
                    return new SingleMatch($n, new Command($this->name, true));
                } else {
                    break;
                }
            }
        }
        return new SingleMatch(null, null);
    }
}
