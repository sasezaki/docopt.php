<?php
namespace Docopt;

/**
 * Exit in case user invoked program with incorrect arguments.
 * DocoptExit equivalent.
 */
class ExitException extends \RuntimeException
{
    /** @var string */
    public static $usage;

    /** @var int */
    public $status;

    /**
     * @param ?string $message
     * @param int $status
     */
    public function __construct($message=null, $status=1)
    {
        parent::__construct(trim($message.PHP_EOL.static::$usage));
        $this->status = $status;
    }
}
