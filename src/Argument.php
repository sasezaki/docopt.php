<?php
namespace Docopt;

class Argument extends LeafPattern
{
    /* {{{ this stuff is against LeafPattern in the python version
     * but it interferes with name() */

    /** @var ?string */
    public $name;

    /** @var mixed */
    public $value;

    /**
     * @param ?string $name
     * @param mixed $value
     */
    public function __construct($name, $value=null)
    {
        $this->name = $name;
        $this->value = $value;
    }
    /* }}} */

    /**
     * @param Pattern[] $left
     * @return SingleMatch
     */
    public function singleMatch($left)
    {
        foreach ($left as $n=>$pattern) {
            if ($pattern instanceof Argument) {
                return new SingleMatch($n, new Argument($this->name, $pattern->value));
            }
        }
        return new SingleMatch(null, null);
    }

    /**
     * @param string $source
     * @return Argument
     */
    public static function parse($source)
    {
        $name = null;
        $value = null;

        if (preg_match_all('@(<\S*?'.'>)@', $source, $matches)) {
            $name = $matches[0][0];
        }
        if (preg_match_all('@\[default: (.*)\]@i', $source, $matches)) {
            $value = $matches[0][1];
        }
        return new static($name, $value);
    }

    /** @return string */
    public function dump()
    {
        return get_class_name($this)."(".dump_scalar($this->name).", ".dump_scalar($this->value).")";
    }
}
