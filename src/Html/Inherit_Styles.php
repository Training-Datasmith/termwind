<?php

declare (strict_types=1);
namespace Termwind\Html;

use Termwind\Components\Element;
use Termwind\Termwind;
use Termwind\Value_Objects\Styles;
/**
 * @internal
 */
final class Inherit_Styles
{
    /**
     * Applies styles from parent element to child elements.
     *
     * @param  array<int, Element|string>  $elements
     * @return array<int, Element|string>
     */
    public function __invoke(array $elements, Styles $styles): array
    {
        $elements = array_values($elements);
        foreach ($elements as &$element) {
            if (is_string($element)) {
                $element = Termwind::raw($element);
            }
            $element->inherit_from_styles($styles);
        }
        /** @var Element[] $elements */
        if (($styles->get_properties()['styles']['display'] ?? 'inline') === 'flex') {
            $elements = $this->apply_flex($elements);
        }
        return match ($styles->get_properties()['styles']['justifyContent'] ?? false) {
            'between' => $this->apply_justify_between($elements),
            'evenly' => $this->apply_justify_evenly($elements),
            'around' => $this->apply_justify_around($elements),
            'center' => $this->apply_justify_center($elements),
            default => $elements,
        };
    }
    /**
     * Applies flex-1 to child elements with the class.
     *
     * @param  array<int, Element>  $elements
     * @return array<int, Element>
     */
    private function apply_flex(array $elements): array
    {
        [$total_width, $parent_width] = $this->get_width_from_elements($elements);
        $width = max(0, array_reduce($elements, fn(float|int $carry, \Termwind\Components\Element $element) => $carry += $element->has_style('flex-1') ? $element->get_inner_width() : 0, $parent_width - $total_width));
        $flexed = array_values(array_filter($elements, fn(\Termwind\Components\Element $element) => $element->has_style('flex-1')));
        foreach ($flexed as $index => &$element) {
            if ($width === 0 && !($element->get_properties()['styles']['contentRepeat'] ?? false)) {
                continue;
            }
            $float = $width / count($flexed);
            $element_width = floor($float);
            if ($index === count($flexed) - 1) {
                $element_width += ($float - floor($float)) * count($flexed);
            }
            $element->add_style("w-{$element_width}");
        }
        return $elements;
    }
    /**
     * Applies the space between the elements.
     *
     * @param  array<int, Element>  $elements
     * @return array<int, Element|string>
     */
    private function apply_justify_between(array $elements): array
    {
        if (count($elements) <= 1) {
            return $elements;
        }
        [$total_width, $parent_width] = $this->get_width_from_elements($elements);
        $space = ($parent_width - $total_width) / (count($elements) - 1);
        if ($space < 1) {
            return $elements;
        }
        $arr = [];
        foreach ($elements as $index => &$element) {
            if ($index !== 0) {
                // Since there is no float pixel, on the last one it should round up...
                $length = $index === count($elements) - 1 ? ceil($space) : floor($space);
                $arr[] = str_repeat(' ', (int) $length);
            }
            $arr[] = $element;
        }
        return $arr;
    }
    /**
     * Applies the space between and around the elements.
     *
     * @param  array<int, Element>  $elements
     * @return array<int, Element|string>
     */
    private function apply_justify_evenly(array $elements): array
    {
        [$total_width, $parent_width] = $this->get_width_from_elements($elements);
        $space = ($parent_width - $total_width) / (count($elements) + 1);
        if ($space < 1) {
            return $elements;
        }
        $arr = [];
        foreach ($elements as &$element) {
            $arr[] = str_repeat(' ', (int) floor($space));
            $arr[] = $element;
        }
        $decimals = ceil(($space - floor($space)) * (count($elements) + 1));
        $arr[] = str_repeat(' ', (int) (floor($space) + $decimals));
        return $arr;
    }
    /**
     * Applies the space around the elements.
     *
     * @param  array<int, Element>  $elements
     * @return array<int, Element|string>
     */
    private function apply_justify_around(array $elements): array
    {
        if (count($elements) === 0) {
            return $elements;
        }
        [$total_width, $parent_width] = $this->get_width_from_elements($elements);
        $space = ($parent_width - $total_width) / count($elements);
        if ($space < 1) {
            return $elements;
        }
        $content_size = $total_width;
        $arr = [];
        foreach ($elements as $index => &$element) {
            if ($index !== 0) {
                $arr[] = str_repeat(' ', (int) ceil($space));
                $content_size += ceil($space);
            }
            $arr[] = $element;
        }
        return [str_repeat(' ', (int) floor(($parent_width - $content_size) / 2)), ...$arr, str_repeat(' ', (int) ceil(($parent_width - $content_size) / 2))];
    }
    /**
     * Applies the space on before first element and after last element.
     *
     * @param  array<int, Element>  $elements
     * @return array<int, Element|string>
     */
    private function apply_justify_center(array $elements): array
    {
        [$total_width, $parent_width] = $this->get_width_from_elements($elements);
        $space = $parent_width - $total_width;
        if ($space < 1) {
            return $elements;
        }
        return [str_repeat(' ', (int) floor($space / 2)), ...$elements, str_repeat(' ', (int) ceil($space / 2))];
    }
    /**
     * Gets the total width for the elements and their parent width.
     *
     * @param  array<int, Element>  $elements
     * @return int[]
     */
    private function get_width_from_elements(array $elements): array
    {
        $total_width = (int) array_reduce($elements, fn($carry, $element): array|float|int => $carry += $element->get_length(), 0);
        $parent_width = Styles::get_parent_width($elements[0]->get_properties()['parentStyles'] ?? []);
        return [$total_width, $parent_width];
    }
}