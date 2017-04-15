<?php
namespace Docopt;

/**
 * Return true if all cased characters in the string are uppercase and there is
 * at least one cased character, false otherwise.
 * Python method with no knowrn equivalent in PHP.
 *
 * @param string $string
 * @return bool
 */
function is_upper($string)
{
    return preg_match('/[A-Z]/', $string) && !preg_match('/[a-z]/', $string);
}

/**
 * Return True if any element of the iterable is true. If the iterable is
 * empty, return False. Python method with no known equivalent in PHP.
 *
 * @param array|\Iterator $iterable
 * @return bool
 */
function any($iterable)
{
    foreach ($iterable as $element) {
        if ($element) {
            return true;
        }
    }
    return false;
}

/**
 * The PHP version of this doesn't support array iterators
 * @param array|\Iterator $input
 * @param callable $callback
 * @param bool $reKey
 * @return array
 */
function array_filter($input, $callback, $reKey=false)
{
    if ($input instanceof \Iterator) {
        $input = iterator_to_array($input);
    }
    $filtered = \array_filter($input, $callback);
    if ($reKey) {
        $filtered = array_values($filtered);
    }
    return $filtered;
}

/**
 * The PHP version of this doesn't support array iterators
 * @param array $values,...
 * @return array
 */
function array_merge()
{
    $values = func_get_args();
    $resolved = array();
    foreach ($values as $v) {
        if ($v instanceof \Iterator) {
            $resolved[] = iterator_to_array($v);
        } else {
            $resolved[] = $v;
        }
    }
    return call_user_func_array('array_merge', $resolved);
}

/**
 * @param string $str
 * @param string $test The suffix to check
 * @return bool
 */
function ends_with($str, $test)
{
    $len = strlen($test);
    return substr_compare($str, $test, -$len, $len) === 0;
}

/**
 * @param mixed $obj
 * @return string
 */
function get_class_name($obj)
{
    $cls = get_class($obj);
    return substr($cls, strpos($cls, '\\')+1);
}

function dumpw($val)
{
    echo dump($val);
    echo PHP_EOL;
}

function dump($val)
{
    $out = "";
    if (is_array($val) || $val instanceof \Traversable) {
        $out = '[';
        $cur = array();
        foreach ($val as $i) {
            if (is_object($i)) {
                $cur[] = $i->dump();
            } elseif (is_array($i)) {
                $cur[] = dump($i);
            } else {
                $cur[] = dump_scalar($i);
            }
        }
        $out .= implode(', ', $cur);
        $out .= ']';
    }
    elseif ($val instanceof Pattern) {
        $out .= $val->dump();
    } else {
        throw new \InvalidArgumentException();
    }
    return $out;
}

function dump_scalar($scalar)
{
    if ($scalar === null) {
        return 'None';
    } elseif ($scalar === false) {
        return 'False';
    } elseif ($scalar === true) {
        return 'True';
    } elseif (is_int($scalar) || is_float($scalar)) {
        return $scalar;
    } else {
        return "'$scalar'";
    }
}

/**
 * Expand pattern into an (almost) equivalent one, but with single Either.
 *
 * Example: ((-a | -b) (-c | -d)) => (-a -c | -a -d | -b -c | -b -d)
 * Quirks: [-a] => (-a), (-a...) => (-a -a)
 *
 * @param Pattern $pattern
 * @return Either
 */
function transform($pattern)
{
    $result = array();
    $groups = array(array($pattern));
    $parents = array('Required', 'Optional', 'OptionsShortcut', 'Either', 'OneOrMore');

    while ($groups) {
        $children = array_shift($groups);
        $types = array();
        foreach ($children as $c) {
            if (is_object($c)) {
                $types[get_class_name($c)] = true;
            }
        }

        if (array_intersect(array_keys($types), $parents)) {
            $child = null;
            foreach ($children as $currentChild) {
                if (in_array(get_class_name($currentChild), $parents)) {
                    $child = $currentChild;
                    break;
                }
            }
            unset($children[array_search($child, $children)]);
            $childClass = get_class_name($child);
            if ($childClass == 'Either') {
                foreach ($child->children as $c) {
                    $groups[] = array_merge(array($c), $children);
                }
            }
            elseif ($childClass == 'OneOrMore') {
                $groups[] = array_merge($child->children, $child->children, $children);
            }
            else {
                $groups[] = array_merge($child->children, $children);
            }
        }
        else {
            $result[] = $children;
        }
    }

    $rs = array();
    foreach ($result as $e) {
        $rs[] = new Required($e);
    }
    return new Either($rs);
}











/**
 * long ::= '--' chars [ ( ' ' | '=' ) chars ] ;
 *
 * @return Option[]
 */
function parse_long(Tokens $tokens, \ArrayIterator $options)
{
    $token = $tokens->move();
    $exploded = explode('=', $token, 2);
    if (count($exploded) == 2) {
        $long = $exploded[0];
        $eq = '=';
        $value = $exploded[1];
    }
    else {
        $long = $token;
        $eq = null;
        $value = null;
    }

    if (strpos($long, '--') !== 0) {
        throw new \UnexpectedValueException("Expected long option, found '$long'");
    }

    $value = (!$eq && !$value) ? null : $value;

    $filter = function($o) use ($long) { return $o->long && $o->long == $long; };
    $similar = array_filter($options, $filter, true);
    if ('ExitException' == $tokens->error && !$similar) {
        $filter = function($o) use ($long) { return $o->long && strpos($o->long, $long)===0; };
        $similar = array_filter($options, $filter, true);
    }

    if (count($similar) > 1) {
        // might be simply specified ambiguously 2+ times?
        $tokens->raiseException("$long is not a unique prefix: ".
            implode(', ', array_map(function($o) { return $o->long; }, $similar)));
    }
    elseif (count($similar) < 1) {
        $argcount = $eq == '=' ? 1 : 0;
        $o = new Option(null, $long, $argcount);
        $options[] = $o;
        if ($tokens->error == 'ExitException') {
            $o = new Option(null, $long, $argcount, $argcount ? $value : true);
        }
    }
    else {
        $o = new Option($similar[0]->short, $similar[0]->long, $similar[0]->argcount, $similar[0]->value);
        if ($o->argcount == 0) {
            if ($value !== null) {
                $tokens->raiseException("{$o->long} must not have an argument");
            }
        }
        else {
            if ($value === null) {
                if ($tokens->current() === null || $tokens->current() == "--") {
                    $tokens->raiseException("{$o->long} requires argument");
                }
                $value = $tokens->move();
            }
        }
        if ($tokens->error == 'ExitException') {
            $o->value = $value !== null ? $value : true;
        }
    }
    return array($o);
}

/**
 * shorts ::= '-' ( chars )* [ [ ' ' ] chars ] ;
 *
 * @return Option[]
 */
function parse_shorts(Tokens $tokens, \ArrayIterator $options)
{
    $token = $tokens->move();

    if (strpos($token, '-') !== 0 || strpos($token, '--') === 0) {
        throw new \UnexpectedValueException("short token '$token' does not start with '-' or '--'");
    }

    $left = ltrim($token, '-');
    $parsed = array();
    while ($left != '') {
        $short = '-'.$left[0];
        $left = substr($left, 1);
        $similar = array();
        foreach ($options as $o) {
            if ($o->short == $short) {
                $similar[] = $o;
            }
        }

        $similarCnt = count($similar);
        if ($similarCnt > 1) {
            $tokens->raiseException("$short is specified ambiguously $similarCnt times");
        }
        elseif ($similarCnt < 1) {
            $o = new Option($short, null, 0);
            $options[] = $o;
            if ($tokens->error == 'ExitException') {
                $o = new Option($short, null, 0, true);
            }
        }
        else {
            $o = new Option($short, $similar[0]->long, $similar[0]->argcount, $similar[0]->value);
            $value = null;
            if ($o->argcount != 0) {
                if ($left == '') {
                    if ($tokens->current() === null || $tokens->current() == '--') {
                        $tokens->raiseException("$short requires argument");
                    }
                    $value = $tokens->move();
                }
                else {
                    $value = $left;
                    $left = '';
                }
            }
            if ($tokens->error == 'ExitException') {
                $o->value = $value !== null ? $value : true;
            }
        }
        $parsed[] = $o;
    }

    return $parsed;
}

/**
 * @param string $source
 * @return Required
 */
function parse_pattern($source, \ArrayIterator $options)
{
    $tokens = Tokens::fromPattern($source);
    $result = parse_expr($tokens, $options);
    if ($tokens->current() != null) {
        $tokens->raiseException('unexpected ending: '.implode(' ', $tokens->left()));
    }
    return new Required($result);
}

/**
 * expr ::= seq ( '|' seq )* ;
 *
 * @return Either|Pattern[]
 */
function parse_expr(Tokens $tokens, \ArrayIterator $options)
{
    $seq = parse_seq($tokens, $options);
    if ($tokens->current() != '|') {
        return $seq;
    }

    $result = null;
    if (count($seq) > 1) {
        $result = array(new Required($seq));
    } else {
        $result = $seq;
    }

    while ($tokens->current() == '|') {
        $tokens->move();
        $seq = parse_seq($tokens, $options);
        if (count($seq) > 1) {
            $result[] = new Required($seq);
        } else {
            $result = array_merge($result, $seq);
        }
    }

    if (count($result) > 1) {
        return new Either($result);
    } else {
        return $result;
    }
}

/**
 * seq ::= ( atom [ '...' ] )* ;
 *
 * @return Pattern[]
 */
function parse_seq(Tokens $tokens, \ArrayIterator $options)
{
    $result = array();
    $not = array(null, '', ']', ')', '|');
    while (!in_array($tokens->current(), $not, true)) {
        $atom = parse_atom($tokens, $options);
        if ($tokens->current() == '...') {
            $atom = array(new OneOrMore($atom));
            $tokens->move();
        }
        if ($atom) {
            $result = array_merge($result, $atom);
        }
    }
    return $result;
}

/**
 * atom ::= '(' expr ')' | '[' expr ']' | 'options'
 *       | long | shorts | argument | command ;
 *
 * @return Pattern[]
 */
function parse_atom(Tokens $tokens, \ArrayIterator $options)
{
    $token = $tokens->current();
    $result = array();

    if ($token == '(' || $token == '[') {
        $tokens->move();

        static $index;
        if (!$index) {
            $index = array('('=>array(')', __NAMESPACE__.'\Required'), '['=>array(']', __NAMESPACE__.'\Optional'));
        }
        list ($matching, $pattern) = $index[$token];

        $result = new $pattern(parse_expr($tokens, $options));
        if ($tokens->move() != $matching) {
            $tokens->raiseException("Unmatched '$token'");
        }

        return array($result);
    }
    elseif ($token == 'options') {
        $tokens->move();
        return array(new OptionsShortcut);
    }
    elseif (strpos($token, '--') === 0 && $token != '--') {
        return parse_long($tokens, $options);
    }
    elseif (strpos($token, '-') === 0 && $token != '-' && $token != '--') {
        return parse_shorts($tokens, $options);
    }
    elseif (strpos($token, '<') === 0 && ends_with($token, '>') || is_upper($token)) {
        return array(new Argument($tokens->move()));
    }
    else {
        return array(new Command($tokens->move()));
    }
}

/**
 * Parse command-line argument vector.
 *
 * If options_first:
 *     argv ::= [ long | shorts ]* [ argument ]* [ '--' [ argument ]* ] ;
 * else:
 *     argv ::= [ long | shorts | argument ]* [ '--' [ argument ]* ] ;
 *
 * @param bool $optionsFirst
 * @return Pattern[]
 */
function parse_argv(Tokens $tokens, \ArrayIterator $options, $optionsFirst=false)
{
    $parsed = array();

    while ($tokens->current() !== null) {
        if ($tokens->current() == '--') {
            while ($tokens->current() !== null) {
                $parsed[] = new Argument(null, $tokens->move());
            }
            return $parsed;
        }
        elseif (strpos($tokens->current(), '--')===0) {
            $parsed = array_merge($parsed, parse_long($tokens, $options));
        }
        elseif (strpos($tokens->current(), '-')===0 && $tokens->current() != '-') {
            $parsed = array_merge($parsed, parse_shorts($tokens, $options));
        }
        elseif ($optionsFirst) {
            return array_merge($parsed, array_map(function($v) { return new Argument(null, $v); }, $tokens->left()));
        }
        else {
            $parsed[] = new Argument(null, $tokens->move());
        }
    }
    return $parsed;
}

/**
 * @param string $doc
 * @return \ArrayIterator
 */
function parse_defaults($doc)
{
    $defaults = array();
    foreach (parse_section('options:', $doc) as $s) {
        # FIXME corner case "bla: options: --foo"
        list (, $s) = explode(':', $s, 2);
        $splitTmp = array_slice(preg_split("@\n[ \t]*(-\S+?)@", "\n".$s, null, PREG_SPLIT_DELIM_CAPTURE), 1);
        $split = array();
        for ($cnt = count($splitTmp), $i=0; $i < $cnt; $i+=2) {
            $split[] = $splitTmp[$i] . (isset($splitTmp[$i+1]) ? $splitTmp[$i+1] : '');
        }
        $options = array();
        foreach ($split as $s) {
            if (strpos($s, '-') === 0) {
                $options[] = Option::parse($s);
            }
        }
        $defaults = array_merge($defaults, $options);
    }

    return new \ArrayIterator($defaults);
}

/**
 * @param string $name
 * @param string $source
 * @return string[]
 */
function parse_section($name, $source)
{
    $ret = array();
    if (preg_match_all('@^([^\n]*'.$name.'[^\n]*\n?(?:[ \t].*?(?:\n|$))*)@im',
        $source, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $ret[] = trim($match[0]);
        }
    }
    return $ret;
}

/**
 * @param string $section
 * @return string
 */
function formal_usage($section)
{
    list (, $section) = explode(':', $section, 2);  # drop "usage:"
    $pu = preg_split('/\s+/', trim($section));

    $ret = array();
    foreach (array_slice($pu, 1) as $s) {
        if ($s == $pu[0]) {
            $ret[] = ') | (';
        } else {
            $ret[] = $s;
        }
    }

    return '( '.implode(' ', $ret).' )';
}

/**
 * @param bool $help
 * @param ?string $version
 * @param Pattern[] $argv
 * @param string $doc
 */
function extras($argv)
{
    $ofound = false;
    $vfound = false;
    foreach ($argv as $o) {
        if ($o->value && ($o->name == '-h' || $o->name == '--help')) {
            $ofound = true;
        }
        if ($o->value && $o->name == '--version') {
            $vfound = true;
        }
    }

    return [$ofound, $vfound];
//    if ($help && $ofound) {
//        ExitException::$usage = null;
//        throw new ExitException($doc, 0);
//    }
//    if ($version && $vfound) {
//        ExitException::$usage = null;
//        throw new ExitException($version, 0);
//    }
}


