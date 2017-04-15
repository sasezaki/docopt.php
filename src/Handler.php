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

            $options = parse_defaults($doc);

            $formalUse = formal_usage($usage);
            $pattern = parse_pattern($formalUse, $options);

            $argv = parse_argv(new Tokens($argv), $options, $this->optionsFirst);

            $patternOptions = $pattern->flat('Option');
            foreach ($pattern->flat('OptionsShortcut') as $optionsShortcut) {
                $docOptions = parse_defaults($doc);
                $optionsShortcut->children = array_diff((array)$docOptions, $patternOptions);
            }

            list($help_argument, $version_argument) = extras($argv);
            if ($this->help && $help_argument) {
                if ($this->exit) {
                    echo $doc, PHP_EOL;
                    exit(0);
                }
                return new Response([], 0, $doc);
            }
            if ($this->version && $version_argument) {
                if ($this->exit) {
                    echo $this->version, PHP_EOL;
                    exit(0);
                }
                return new Response([], 0, $this->version);
            }

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

            $message = !$this->exitFullUsage ? $usage : $doc;

            if ($this->exit) {
                echo $message, PHP_EOL;
                exit(1);
            }

            return new Response([], 1, $message);

        } catch (ExitException $ex) {
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
