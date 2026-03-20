<?php

declare (strict_types=1);
namespace Termwind\Html;

use Termwind\Components\Element;
use Termwind\Termwind;
use Termwind\Value_Objects\Node;
/**
 * @internal
 */
final class Pre_Renderer
{
    /**
     * Gets HTML content from a given node and converts to the content element.
     */
    public function to_element(Node $node): \Termwind\Components\Raw
    {
        $lines = explode("\n", $node->get_html());
        if (reset($lines) === '') {
            array_shift($lines);
        }
        if (end($lines) === '') {
            array_pop($lines);
        }
        $max_str_len = array_reduce($lines, static fn(int $max, string $line): int => $max < strlen($line) ? strlen($line) : $max, 0);
        $styles = $node->get_class_attribute();
        $html = array_map(static fn(string $line): string => (string) Termwind::div(str_pad($line, $max_str_len + 3), $styles), $lines);
        return Termwind::raw(implode('', $html));
    }
}