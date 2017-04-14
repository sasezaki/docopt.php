<?php
namespace Docopt;


class Optional extends BranchPattern
{
    /**
     * @param Pattern[] $left
     * @param Pattern[] $collected
     */
    public function match($left, $collected=null)
    {
        if (!$collected) {
            $collected = array();
        }

        foreach ($this->children as $pattern) {
            list($m, $left, $collected) = $pattern->match($left, $collected);
        }

        return array(true, $left, $collected);
    }
}
