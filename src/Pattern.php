<?php
namespace Docopt;


abstract class Pattern
{
    /** @var Pattern[] */
    public $children = array();

    /**
     * @param string[]|string $types
     * @return Pattern[]
     */
    abstract function flat($types=array());

    /**
     * @param Pattern[] $left
     * @param Pattern[] $collected
     */
    abstract function match($left, $collected=null);

    /** @return string */
    function name() { return ''; }

    /** @return string */
    function dump() { return ''; }

    /** @return string */
    public function __toString()
    {
        return serialize($this);
    }

    /** @return string */
    public function hash()
    {
        return (string) crc32((string)$this);
    }

    /** @return $this */
    public function fix()
    {
        $this->fixIdentities();
        $this->fixRepeatingArguments();
        return $this;
    }

    /**
     * Make pattern-tree tips point to same object if they are equal.
     *
     * @param Pattern[]|null $uniq
     */
    public function fixIdentities($uniq=null)
    {
        if (!isset($this->children) || !$this->children) {
            return $this;
        }
        if ($uniq === null) {
            $uniq = array_unique($this->flat());
        }

        foreach ($this->children as $i=>$child) {
            if (!$child instanceof BranchPattern) {
                if (!in_array($child, $uniq)) {
                    // Not sure if this is a true substitute for 'assert c in uniq'
                    throw new \UnexpectedValueException();
                }
                $this->children[$i] = $uniq[array_search($child, $uniq)];
            }
            else {
                $child->fixIdentities($uniq);
            }
        }
    }

    /**
     * Fix elements that should accumulate/increment values.
     * @return $this
     */
    public function fixRepeatingArguments()
    {
        $either = array();
        foreach (transform($this)->children as $child) {
            $either[] = $child->children;
        }

        foreach ($either as $case) {
            $counts = array();
            foreach ($case as $child) {
                $ser = serialize($child);
                if (!isset($counts[$ser])) {
                    $counts[$ser] = array('cnt'=>0, 'items'=>array());
                }

                $counts[$ser]['cnt']++;
                $counts[$ser]['items'][] = $child;
            }

            $repeatedCases = array();
            foreach ($counts as $child) {
                if ($child['cnt'] > 1) {
                    $repeatedCases = array_merge($repeatedCases, $child['items']);
                }
            }

            foreach ($repeatedCases as $e) {
                if ($e instanceof Argument || ($e instanceof Option && $e->argcount)) {
                    if (!$e->value) {
                        $e->value = array();
                    } elseif (!is_array($e->value) && !$e->value instanceof \Traversable) {
                        $e->value = preg_split('/\s+/', $e->value);
                    }
                }
                if ($e instanceof Command || ($e instanceof Option && $e->argcount == 0)) {
                    $e->value = 0;
                }
            }
        }

        return $this;
    }

    public function __get($name)
    {
        if ($name == 'name') {
            return $this->name();
        } else {
            throw new \BadMethodCallException("Unknown property $name");
        }
    }
}
