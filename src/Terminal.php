<?php

declare(strict_types=1);

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
    public function __construct(private ?ConsoleTerminal $terminal = new ConsoleTerminal())
    {
    }

    /**
     * Gets the terminal width.
     */
    public function width(): int
    {
        return $this->terminal->getWidth();
    }

    /**
     * Gets the terminal height.
     */
    public function height(): int
    {
        return $this->terminal->getHeight();
    }

    /**
     * Clears the terminal screen.
     */
    public function clear(): void
    {
        Termwind::getRenderer()->write("\ec");
    }
}
