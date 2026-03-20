<?php

declare (strict_types=1);
namespace Termwind;

use Symfony\Component\Console\Terminal as ConsoleTerminal;
/**
 * @internal
 */
final readonly class Terminal
{
    /**
     * Creates a new terminal instance.
     */
    public function __construct(private ?Console_Terminal $terminal = new Console_Terminal())
    {
    }
    /**
     * Gets the terminal width.
     */
    public function width(): int
    {
        return $this->terminal->get_width();
    }
    /**
     * Gets the terminal height.
     */
    public function height(): int
    {
        return $this->terminal->get_height();
    }
    /**
     * Clears the terminal screen.
     */
    public function clear(): void
    {
        Termwind::get_renderer()->write("\x1bc");
    }
}