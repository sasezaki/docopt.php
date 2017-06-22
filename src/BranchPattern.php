<?php

namespace Docopt;

class BranchPattern extends Pattern
{
    /**
     * @param Pattern[]|Pattern $children
     */
    public function __construct($children=null)
    {
        if (!$children) {
            $children = array();
        } elseif ($children instanceof Pattern) {
            $children = func_get_args();
        }
        foreach ($children as $child) {
            $this->children[] = $child;
        }
    }

    /**
     * @param string[]|string $types
     * @return Pattern[]
     */
    public function flat($types=array())
    {
        $types = is_array($types) ? $types : array($types);

        if (in_array(get_class_name($this), $types)) {
            return array($this);
        }
        $flat = array();
        foreach ($this->children as $c) {
            $flat = array_merge($flat, $c->flat($types));
        }
        return $flat;
    }

    /** @return string */
    public function dump()
    {
        $out = get_class_name($this).'(';
        $cd = array();
        foreach ($this->children as $c) {
            $cd[] = $c->dump();
        }
        $out .= implode(', ', $cd).')';
        return $out;
    }

    /**
     * @param Pattern[] $left
     * @param Pattern[] $collected
     */
    public function match($left, $collected=null)
    {
        throw new \RuntimeException("Unsupported");
    }
}
