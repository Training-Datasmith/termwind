<?php

declare(strict_types=1);

use Symfony\Component\Console\Output\BufferedOutput;

use function Termwind\renderUsing;

use Termwind\Repositories\Styles;

uses()->beforeEach(fn () => renderUsing($this->output = new BufferedOutput()))
    ->afterEach(function () {
        renderUsing(null);

        Styles::flush();
    })->in(__DIR__);

/**
 * Gets a input stream resource from a string.
 *
 * @return resource
 */
function getInputStream(string $input = 'answer')
{
    $stream = fopen('php://memory', 'r+', false);
    fwrite($stream, $input);
    rewind($stream);

    return $stream;
}
