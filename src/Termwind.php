<?php

declare (strict_types=1);
namespace Termwind;

use Closure;
use Symfony\Component\Console\Output\Console_Output;
use Symfony\Component\Console\Output\Output_Interface;
use Termwind\Components\Element;
use Termwind\Exceptions\Invalid_Child;
/**
 * @internal
 */
final class Termwind
{
    /**
     * The implementation of the output.
     */
    private static ?Output_Interface $renderer;
    /**
     * Sets the renderer implementation.
     */
    public static function render_using(?Output_Interface $renderer): void
    {
        self::$renderer = $renderer ?? new Console_Output();
    }
    /**
     * Creates a div element instance.
     *
     * @param  array<int, Element|string>|string  $content
     * @param  array<string, mixed>  $properties
     */
    public static function div(array|string $content = '', string $styles = '', array $properties = []): Components\Div
    {
        $content = self::prepare_elements($content);
        return Components\Div::from_styles(self::get_renderer(), $content, $styles, $properties);
    }
    /**
     * Creates a paragraph element instance.
     *
     * @param  array<int, Element|string>|string  $content
     * @param  array<string, mixed>  $properties
     */
    public static function paragraph(array|string $content = '', string $styles = '', array $properties = []): Components\Paragraph
    {
        $content = self::prepare_elements($content);
        return Components\Paragraph::from_styles(self::get_renderer(), $content, $styles, $properties);
    }
    /**
     * Creates a span element instance with the given style.
     *
     * @param  array<int, Element|string>|string  $content
     * @param  array<string, mixed>  $properties
     */
    public static function span(array|string $content = '', string $styles = '', array $properties = []): Components\Span
    {
        $content = self::prepare_elements($content);
        return Components\Span::from_styles(self::get_renderer(), $content, $styles, $properties);
    }
    /**
     * Creates an element instance with raw content.
     *
     * @param  array<int, Element|string>|string  $content
     */
    public static function raw(array|string $content = ''): Components\Raw
    {
        return Components\Raw::from_styles(self::get_renderer(), $content);
    }
    /**
     * Creates an anchor element instance with the given style.
     *
     * @param  array<int, Element|string>|string  $content
     * @param  array<string, mixed>  $properties
     */
    public static function anchor(array|string $content = '', string $styles = '', array $properties = []): Components\Anchor
    {
        $content = self::prepare_elements($content);
        return Components\Anchor::from_styles(self::get_renderer(), $content, $styles, $properties);
    }
    /**
     * Creates an unordered list instance.
     *
     * @param  array<int, string|Element>  $content
     * @param  array<string, mixed>  $properties
     */
    public static function ul(array $content = [], string $styles = '', array $properties = []): Components\Ul
    {
        $ul = Components\Ul::from_styles(self::get_renderer(), '', $styles, $properties);
        $content = self::prepare_elements($content, static function ($li) use ($ul): string|Element {
            if (is_string($li)) {
                return $li;
            }
            if (!$li instanceof Components\Li) {
                throw new Invalid_Child('Unordered lists only accept `li` as child');
            }
            return match (true) {
                $li->has_style('list-none') => $li,
                $ul->has_style('list-none') => $li->add_style('list-none'),
                $ul->has_style('list-square') => $li->add_style('list-square'),
                $ul->has_style('list-disc') => $li->add_style('list-disc'),
                default => $li->add_style('list-none'),
            };
        });
        return $ul->set_content($content);
    }
    /**
     * Creates an ordered list instance.
     *
     * @param  array<int, string|Element>  $content
     * @param  array<string, mixed>  $properties
     */
    public static function ol(array $content = [], string $styles = '', array $properties = []): Components\Ol
    {
        $ol = Components\Ol::from_styles(self::get_renderer(), '', $styles, $properties);
        $index = 0;
        $content = self::prepare_elements($content, static function ($li) use ($ol, &$index): string|Element {
            if (is_string($li)) {
                return $li;
            }
            if (!$li instanceof Components\Li) {
                throw new Invalid_Child('Ordered lists only accept `li` as child');
            }
            return match (true) {
                $li->has_style('list-none') => $li->add_style('list-none'),
                $ol->has_style('list-none') => $li->add_style('list-none'),
                $ol->has_style('list-decimal') => $li->add_style('list-decimal-' . ++$index),
                default => $li->add_style('list-none'),
            };
        });
        return $ol->set_content($content);
    }
    /**
     * Creates a list item instance.
     *
     * @param  array<int, Element|string>|string  $content
     * @param  array<string, mixed>  $properties
     */
    public static function li(array|string $content = '', string $styles = '', array $properties = []): Components\Li
    {
        $content = self::prepare_elements($content);
        return Components\Li::from_styles(self::get_renderer(), $content, $styles, $properties);
    }
    /**
     * Creates a description list instance.
     *
     * @param  array<int, string|Element>  $content
     * @param  array<string, mixed>  $properties
     */
    public static function dl(array $content = [], string $styles = '', array $properties = []): Components\Dl
    {
        $content = self::prepare_elements($content, static function ($element): string|Element {
            if (is_string($element)) {
                return $element;
            }
            if (!$element instanceof Components\Dt && !$element instanceof Components\Dd) {
                throw new Invalid_Child('Description lists only accept `dt` and `dd` as children');
            }
            return $element;
        });
        return Components\Dl::from_styles(self::get_renderer(), $content, $styles, $properties);
    }
    /**
     * Creates a description term instance.
     *
     * @param  array<int, Element|string>|string  $content
     * @param  array<string, mixed>  $properties
     */
    public static function dt(array|string $content = '', string $styles = '', array $properties = []): Components\Dt
    {
        $content = self::prepare_elements($content);
        return Components\Dt::from_styles(self::get_renderer(), $content, $styles, $properties);
    }
    /**
     * Creates a description details instance.
     *
     * @param  array<int, Element|string>|string  $content
     * @param  array<string, mixed>  $properties
     */
    public static function dd(array|string $content = '', string $styles = '', array $properties = []): Components\Dd
    {
        $content = self::prepare_elements($content);
        return Components\Dd::from_styles(self::get_renderer(), $content, $styles, $properties);
    }
    /**
     * Creates a horizontal rule instance.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function hr(string $styles = '', array $properties = []): Components\Hr
    {
        return Components\Hr::from_styles(self::get_renderer(), '', $styles, $properties);
    }
    /**
     * Creates an break line element instance.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function break_line(string $styles = '', array $properties = []): Components\Break_Line
    {
        return Components\Break_Line::from_styles(self::get_renderer(), '', $styles, $properties);
    }
    /**
     * Gets the current renderer instance.
     */
    public static function get_renderer(): Output_Interface
    {
        return self::$renderer ??= new Console_Output();
    }
    /**
     * Convert child elements to a string.
     *
     * @param  array<int, string|Element>|string  $elements
     * @return array<int, string|Element>
     */
    private static function prepare_elements(string|array $elements, ?Closure $callback = null): array
    {
        if ($callback === null) {
            $callback = static fn($element): string|Element => $element;
        }
        $elements = is_array($elements) ? $elements : [$elements];
        return array_map($callback, $elements);
    }
}