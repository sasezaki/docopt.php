<?php
namespace Docopt;


class OneOrMore extends BranchPattern
{
    /**
     * @param Pattern[] $left
     * @param Pattern[] $collected
     * @return array
     */
    public function match($left, $collected=null)
    {
        if (count($this->children) != 1) {
            throw new \UnexpectedValueException();
        }
        if (!$collected) {
            $collected = array();
        }

        $l = $left;
        $c = $collected;

        $lnew = array();
        $matched = true;
        $times = 0;

        while ($matched) {
            # could it be that something didn't match but changed l or c?
            list ($matched, $l, $c) = $this->children[0]->match($l, $c);
            if ($matched) $times += 1;
            if ($lnew == $l) {
                break;
            }
            $lnew = $l;
        }

        if ($times >= 1) {
            return array(true, $l, $c);
        } else {
            return array(false, $left, $collected);
        }
    }
}
