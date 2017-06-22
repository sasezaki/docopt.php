<?php
namespace Docopt;



class Either extends BranchPattern
{
    /**
     * @param Pattern[] $left
     * @param Pattern[] $collected
     * @return array|mixed|null
     */
    public function match($left, $collected=null)
    {
        if (!$collected) {
            $collected = array();
        }

        $outcomes = array();
        foreach ($this->children as $pattern) {
            list ($matched, $dump1, $dump2) = $outcome = $pattern->match($left, $collected);
            if ($matched) {
                $outcomes[] = $outcome;
            }
        }
        if ($outcomes) {
            // return min(outcomes, key=lambda outcome: len(outcome[1]))
            $min = null;
            $ret = null;
            foreach ($outcomes as $o) {
                $cnt = count($o[1]);
                if ($min === null || $cnt < $min) {
                    $min = $cnt;
                    $ret = $o;
                }
            }
            return $ret;
        }
        else {
            return array(false, $left, $collected);
        }
    }
}
