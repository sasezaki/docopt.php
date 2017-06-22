<?php
namespace Docopt;

class Option extends LeafPattern
{
    /** @var ?string */
    public $short;

    /** @var ?string */
    public $long;

    /** @var int */
    public $argcount;

    /** @var bool|string|null */
    public $value;

    /**
     * @param ?string $short
     * @param ?string $long
     * @param int $argcount
     * @param bool|string|null $value
     */
    public function __construct($short=null, $long=null, $argcount=0, $value=false)
    {
        if ($argcount != 0 && $argcount != 1) {
            throw new \InvalidArgumentException();
        }

        $this->short = $short;
        $this->long = $long;
        $this->argcount = $argcount;
        $this->value = $value;

        if ($value === false && $argcount) {
            $this->value = null;
        }
    }

    /**
     * @param string
     * @return Option
     */
    public static function parse($optionDescription)
    {
        $short = null;
        $long = null;
        $argcount = 0;
        $value = false;

        $exp = explode('  ', trim($optionDescription), 2);
        $options = $exp[0];
        $description = isset($exp[1]) ? $exp[1] : '';

        $options = str_replace(',', ' ', str_replace('=', ' ', $options));
        foreach (preg_split('/\s+/', $options) as $s) {
            if (strpos($s, '--')===0) {
                $long = $s;
            } elseif ($s && $s[0] == '-') {
                $short = $s;
            } else {
                $argcount = 1;
            }
        }

        if ($argcount) {
            $value = null;
            if (preg_match('@\[default: (.*)\]@i', $description, $match)) {
                $value = $match[1];
            }
        }

        return new static($short, $long, $argcount, $value);
    }

    /**
     * @param Pattern[] $left
     * @return SingleMatch
     */
    public function singleMatch($left)
    {
        foreach ($left as $n=>$pattern) {
            if ($this->name == $pattern->name) {
                return new SingleMatch($n, $pattern);
            }
        }
        return new SingleMatch(null, null);
    }

    /** @return string */
    public function name()
    {
        return $this->long ?: $this->short;
    }

    /** @return string */
    public function dump()
    {
        return "Option(".dump_scalar($this->short).", ".dump_scalar($this->long).", ".dump_scalar($this->argcount).", ".dump_scalar($this->value).")";
    }
}
