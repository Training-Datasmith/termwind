<?php

declare (strict_types=1);
namespace Termwind\Actions;

use Termwind\Exceptions\Style_Not_Found;
use Termwind\Repositories\Styles as StyleRepository;
use Termwind\Terminal;
use Termwind\Value_Objects\Styles;
/**
 * @internal
 */
final readonly class Style_To_Method
{
    /**
     * Finds if there is any media query on the style class.
     */
    private const MEDIA_QUERIES_REGEX = "/^(sm|md|lg|xl|2xl)\\:(.*)/";
    /**
     * Defines the Media Query Breakpoints.
     */
    public const MEDIA_QUERY_BREAKPOINTS = ['sm' => 64, 'md' => 76, 'lg' => 102, 'xl' => 128, '2xl' => 153];
    /**
     * Creates a new action instance.
     */
    public function __construct(private Styles $styles, private string $style)
    {
        // ..
    }
    /**
     * Applies multiple styles to the given styles.
     */
    public static function multiple(Styles $styles, string $styles_string): Styles
    {
        $styles_string = self::sort_styles(array_merge($styles->default_styles(), array_filter((array) preg_split('/(?![^\[]*\])\s/', $styles_string))));
        foreach ($styles_string as $style) {
            $styles = (new self($styles, $style))->__invoke();
        }
        return $styles;
    }
    /**
     * Converts the given style to a method name.
     */
    public function __invoke(string|int ...$arguments): Styles
    {
        if (Style_Repository::has($this->style)) {
            return Style_Repository::get($this->style)($this->styles, ...$arguments);
        }
        $method = $this->apply_media_query($this->style);
        if ($method === '') {
            return $this->styles;
        }
        $method = array_filter((array) preg_split('/(?![^\[]*\])-/', $method), fn($item): bool => $item !== false);
        $method = array_slice($method, 0, count($method) - count($arguments));
        $method_name = implode(' ', $method);
        $method_name = ucwords($method_name);
        $method_name = lcfirst($method_name);
        $method_name = str_replace(' ', '', $method_name);
        if ($method_name === '') {
            throw Style_Not_Found::from_style($this->style);
        }
        if (!method_exists($this->styles, $method_name)) {
            $argument = array_pop($method);
            $arguments[] = is_numeric($argument) ? (int) $argument : (string) $argument;
            return $this->__invoke(...$arguments);
        }
        // @phpstan-ignore-next-line
        return $this->styles->set_style($this->style)->{$method_name}(...array_reverse($arguments));
    }
    /**
     * Sorts all the styles based on the correct render order.
     *
     * @param  string[]  $styles
     * @return string[]
     */
    private static function sort_styles(array $styles): array
    {
        $keys = array_keys(self::MEDIA_QUERY_BREAKPOINTS);
        usort($styles, function ($a, $b) use ($keys): int {
            $exists_a = (bool) preg_match(self::MEDIA_QUERIES_REGEX, $a, $matches_a);
            $exists_b = (bool) preg_match(self::MEDIA_QUERIES_REGEX, $b, $matches_b);
            if ($exists_a && !$exists_b) {
                return 1;
            }
            if ($exists_a && array_search($matches_a[1], $keys, true) > array_search($matches_b[1], $keys, true)) {
                return 1;
            }
            return -1;
        });
        return $styles;
    }
    /**
     * Applies the media query if exists.
     */
    private function apply_media_query(string $method): string
    {
        $matches = [];
        preg_match(self::MEDIA_QUERIES_REGEX, $method, $matches);
        if (count($matches) < 1) {
            return $method;
        }
        [, $size, $method] = $matches;
        if ((new Terminal())->width() >= self::MEDIA_QUERY_BREAKPOINTS[$size]) {
            return $method;
        }
        return '';
    }
}