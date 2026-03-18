<?php

declare(strict_types=1);

use Termwind\Termwind;

it('adds a raw content', function () {
    $content = "<bg=default;options=></>\n";

    $br = Termwind::raw($content);

    expect($br->toString())->toBe($content);
});
