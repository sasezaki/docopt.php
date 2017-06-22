<?php
namespace Docopt;


class Required extends BranchPattern
{
    /**
     * @param Pattern[] $left
     * @param Pattern[] $collected
     * @return array
     */
    public function match($left, $collected=null)
    {
        if (!$collected) {
            $collected = array();
        }

        $l = $left;
        $c = $collected;

        foreach ($this->children as $pattern) {
            list ($matched, $l, $c) = $pattern->match($l, $c);
            if (!$matched) {
                return array(false, $left, $collected);
            }
        }

        return array(true, $l, $c);
    }
}

