<?php

declare (strict_types=1);
namespace Termwind\Components;

final class Break_Line extends Element
{
    /**
     * Get the string representation of the element.
     */
    public function to_string(): string
    {
        $display = $this->styles->get_properties()['styles']['display'] ?? 'inline';
        if ($display === 'hidden') {
            return '';
        }
        if ($display === 'block') {
            return parent::to_string();
        }
        return parent::to_string() . "\r";
    }
}