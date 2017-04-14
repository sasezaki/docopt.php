<?php

namespace Docopt;

abstract class LeafPattern extends Pattern
{
    /**
     * @param Pattern[] $left
     * @return SingleMatch
     */
    abstract function singleMatch($left);

    /**
     * @param string[]|string $types
     * @return Pattern[]
     */
    public function flat($types=array())
    {
        $types = is_array($types) ? $types : array($types);

        if (!$types || in_array(get_class_name($this), $types)) {
            return array($this);
        } else {
            return array();
        }
    }

    /**
     * @param Pattern[] $left
     * @param Pattern[] $collected
     */
    public function match($left, $collected=null)
    {
        if (!$collected) {
            $collected = array();
        }

        list ($pos, $match) = $this->singleMatch($left)->toArray();
        if (!$match) {
            return array(false, $left, $collected);
        }

        $left_ = $left;
        unset($left_[$pos]);
        $left_ = array_values($left_);

        $name = $this->name;
        $sameName = array_filter($collected, function ($a) use ($name) { return $name == $a->name; }, true);

        if (is_int($this->value) || is_array($this->value) || $this->value instanceof \Traversable) {
            if (is_int($this->value)) {
                $increment = 1;
            } else {
                $increment = is_string($match->value) ? array($match->value) : $match->value;
            }

            if (!$sameName) {
                $match->value = $increment;
                return array(true, $left_, array_merge($collected, array($match)));
            }

            if (is_array($increment) || $increment instanceof \Traversable) {
                $sameName[0]->value = array_merge($sameName[0]->value, $increment);
            } else {
                $sameName[0]->value += $increment;
            }

            return array(true, $left_, $collected);
        }

        return array(true, $left_, array_merge($collected, array($match)));
    }
}
