<?php

declare (strict_types=1);
namespace Termwind\Exceptions;

use InvalidArgumentException;
/**
 * @internal
 */
final class Style_Not_Found extends InvalidArgumentException
{
    /**
     * Creates a new style not found instance.
     */
    private function __construct(string $message)
    {
        parent::__construct($message, 0, $this->get_previous());
    }
    /**
     * Creates a new style not found instance from the given style.
     */
    public static function from_style(string $style): self
    {
        return new self(sprintf('Style [%s] not found.', $style));
    }
}