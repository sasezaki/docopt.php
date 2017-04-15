<?php

namespace Docopt;

class Handler
{
    /** @var bool */
    private $exit = true;

    /** @var bool */
    private $exitFullUsage = false;

    /** @var bool */
    private $help = true;

    /** @var bool */
    private $optionsFirst = false;

    /** @var ?string */
    private $version;

    public function __construct($options=array())
    {
        foreach ($options as $k=>$v) {
            $this->$k = $v;
        }
    }

    /**
     * @param $doc
     * @param array|string|null $argv
     * @return Response
     */
    public function handle($doc, $argv=null)
    {
        $response = $this->_handle($doc, $argv);

        if (is_string($response)) {
            echo $response, PHP_EOL;
            exit(0);
        }

        if ($response->status !== 0 && $this->exit) {
            echo $response->output, PHP_EOL;
            exit($response->status);
        }
        return $response;
    }

    /**
     * @param $doc
     * @param array|string|null $argv
     * @return Response|string
     * @throws LanguageError
     */
    protected function _handle($doc, $argv=null)
    {
        try {
            if ($argv === null && isset($_SERVER['argv'])) {
                $argv = array_slice($_SERVER['argv'], 1);
            }

            $usageSections = parse_section('usage:', $doc);
            if (count($usageSections) === 0) {
                throw new LanguageError('"usage:" (case-insensitive) not found.');
            } elseif (count($usageSections) > 1) {
                throw new LanguageError('More than one "usage:" (case-insensitive).');
            }
            $usage = $usageSections[0];

            $options = parse_defaults($doc);

            $formalUse = formal_usage($usage);
            $pattern = parse_pattern($formalUse, $options);

            /** @var Pattern[] $argv */
            $argv = parse_argv(new Tokens($argv), $options, $this->optionsFirst);

            $patternOptions = $pattern->flat('Option');
            foreach ($pattern->flat('OptionsShortcut') as $optionsShortcut) {
                $docOptions = parse_defaults($doc);
                $optionsShortcut->children = array_diff((array)$docOptions, $patternOptions);
            }

            list($help_argument, $version_argument) = extras($argv);
            if ($this->help && $help_argument) {
                return $doc;
            }
            if ($this->version && $version_argument) {
                return $this->version;
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
            return new Response([], 1, $message);
        } catch (ExitException $ex) {
            return new Response([], $ex->status, $ex->getMessage());
        }
    }
}
