<?php

declare(strict_types=1);

use function Termwind\parse;

it('renders the element', function () {
    $html = parse('<p>text</p>');

    expect($html)->toBe("\ntext\n");
});
