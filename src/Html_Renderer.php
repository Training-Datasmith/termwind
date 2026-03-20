<?php

declare (strict_types=1);
namespace Termwind;

use Dom_Document;
use Dom_Node;
use Termwind\Html\Code_Renderer;
use Termwind\Html\Pre_Renderer;
use Termwind\Html\Table_Renderer;
use Termwind\Value_Objects\Node;
/**
 * @internal
 */
final class Html_Renderer
{
    /**
     * Renders the given html.
     */
    public function render(string $html, int $options): void
    {
        $this->parse($html)->render($options);
    }
    /**
     * Parses the given html.
     */
    public function parse(string $html): Components\Element
    {
        $dom = new Dom_Document();
        if (strip_tags($html) === $html) {
            return Termwind::span($html);
        }
        $html = '<?xml encoding="UTF-8"><!DOCTYPE html><html><body>' . trim($html) . '</body></html>';
        $dom->load_html($html, LIBXML_NOERROR | LIBXML_COMPACT | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS | LIBXML_NOXMLDECL);
        /** @var DOMNode $body */
        $body = $dom->get_elements_by_tag_name('body')->item(0);
        $el = $this->convert(new Node($body));
        // @codeCoverageIgnoreStart
        return is_string($el) ? Termwind::span($el) : $el;
        // @codeCoverageIgnoreEnd
    }
    /**
     * Convert a tree of DOM nodes to a tree of termwind elements.
     */
    private function convert(Node $node): Components\Element|string
    {
        $children = [];
        if ($node->is_name('table')) {
            return (new Table_Renderer())->to_element($node);
        }
        if ($node->is_name('code')) {
            return (new Code_Renderer())->to_element($node);
        }
        if ($node->is_name('pre')) {
            return (new Pre_Renderer())->to_element($node);
        }
        foreach ($node->get_child_nodes() as $child) {
            $children[] = $this->convert($child);
        }
        $children = array_filter($children, fn(string|\Termwind\Components\Element $child): bool => $child !== '');
        return $this->to_element($node, $children);
    }
    /**
     * Convert a given DOM node to it's termwind element equivalent.
     *
     * @param  array<int, Components\Element|string>  $children
     */
    private function to_element(Node $node, array $children): Components\Element|string
    {
        if ($node->is_text() || $node->is_comment()) {
            return (string) $node;
        }
        /** @var array<string, mixed> $properties */
        $properties = ['isFirstChild' => $node->is_first_child()];
        $styles = $node->get_class_attribute();
        return match ($node->get_name()) {
            'body' => $children[0],
            // Pick only the first element from the body node
            'div' => Termwind::div($children, $styles, $properties),
            'p' => Termwind::paragraph($children, $styles, $properties),
            'ul' => Termwind::ul($children, $styles, $properties),
            'ol' => Termwind::ol($children, $styles, $properties),
            'li' => Termwind::li($children, $styles, $properties),
            'dl' => Termwind::dl($children, $styles, $properties),
            'dt' => Termwind::dt($children, $styles, $properties),
            'dd' => Termwind::dd($children, $styles, $properties),
            'span' => Termwind::span($children, $styles, $properties),
            'br' => Termwind::break_line($styles, $properties),
            'strong' => Termwind::span($children, $styles, $properties)->strong(),
            'b' => Termwind::span($children, $styles, $properties)->font_bold(),
            'em', 'i' => Termwind::span($children, $styles, $properties)->italic(),
            'u' => Termwind::span($children, $styles, $properties)->underline(),
            's' => Termwind::span($children, $styles, $properties)->line_through(),
            'a' => Termwind::anchor($children, $styles, $properties)->href($node->get_attribute('href')),
            'hr' => Termwind::hr($styles, $properties),
            default => Termwind::div($children, $styles, $properties),
        };
    }
}