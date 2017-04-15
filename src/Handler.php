<?php

namespace Docopt;

class Handler
{
    /** @var bool */
    public $exit = true;

    /** @var bool */
    public $exitFullUsage = false;

    /** @var bool */
    public $help = true;

    /** @var bool */
    public $optionsFirst = false;

    /** @var ?string */
    public $version;

    public function __construct($options=array())
    {
        foreach ($options as $k=>$v) {
            $this->$k = $v;
        }
    }

    /**
     * @param $doc
     * @param null $argv
     * @return Response
     * @throws LanguageError
     */
    function handle($doc, $argv=null)
    {
        try {
            if ($argv === null && isset($_SERVER['argv'])) {
                $argv = array_slice($_SERVER['argv'], 1);
            }

            $usageSections = parse_section('usage:', $doc);
            if (count($usageSections) == 0) {
                throw new LanguageError('"usage:" (case-insensitive) not found.');
            } elseif (count($usageSections) > 1) {
                throw new LanguageError('More than one "usage:" (case-insensitive).');
            }
            $usage = $usageSections[0];

            // temp fix until python port provides solution
            ExitException::$usage = !$this->exitFullUsage ? $usage : $doc;

            $options = parse_defaults($doc);

            $formalUse = formal_usage($usage);
            $pattern = parse_pattern($formalUse, $options);

            $argv = parse_argv(new Tokens($argv), $options, $this->optionsFirst);

            $patternOptions = $pattern->flat('Option');
            foreach ($pattern->flat('OptionsShortcut') as $optionsShortcut) {
                $docOptions = parse_defaults($doc);
                $optionsShortcut->children = array_diff((array)$docOptions, $patternOptions);
            }

            extras($this->help, $this->version, $argv, $doc);

            list($matched, $left, $collected) = $pattern->fix()->match($argv);
            if ($matched && !$left) {
                $return = array();
                foreach (array_merge($pattern->flat(), $collected) as $a) {
                    $name = $a->name;
                    if ($name) {
                        $return[$name] = $a->value;
                    }
                }
                return new Response($return);
            }
            throw new ExitException();
        }
        catch (ExitException $ex) {
            $this->handleExit($ex);
            return new Response([], $ex->status, $ex->getMessage());
        }
    }

    function handleExit(ExitException $ex)
    {
        if ($this->exit) {
            echo $ex->getMessage().PHP_EOL;
            exit($ex->status);
        }
    }
}
