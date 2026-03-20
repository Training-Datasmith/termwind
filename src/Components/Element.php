<?php

declare (strict_types=1);
namespace Termwind\Components;

use Symfony\Component\Console\Output\Output_Interface;
use Termwind\Actions\Style_To_Method;
use Termwind\Html\Inherit_Styles;
use Termwind\Value_Objects\Styles;
/**
 * @internal
 *
 * @method Element inheritFromStyles(Styles $styles)
 * @method Element fontBold()
 * @method Element strong()
 * @method Element italic()
 * @method Element underline()
 * @method Element lineThrough()
 * @method int getLength()
 * @method int getInnerWidth()
 * @method array getProperties()
 * @method Element href(string $href)
 * @method bool hasStyle(string $style)
 * @method Element addStyle(string $style)
 */
abstract class Element implements \Stringable
{
    /** @var string[] */
    protected static array $default_styles = [];
    protected Styles $styles;
    /**
     * Creates an element instance.
     *
     * @param  array<int, Element|string>|string  $content
     */
    final public function __construct(protected Output_Interface $output, protected array|string $content, ?Styles $styles = null)
    {
        $this->styles = $styles ?? new Styles(defaultStyles: static::$default_styles);
        $this->styles->set_element($this);
    }
    /**
     * Creates an element instance with the given styles.
     *
     * @param  array<int, Element|string>|string  $content
     * @param  array<string, mixed>  $properties
     */
    final public static function from_styles(Output_Interface $output, array|string $content, string $styles = '', array $properties = []): static
    {
        $element = new static($output, $content);
        if ($properties !== []) {
            $element->styles->set_properties($properties);
        }
        $element_styles = Style_To_Method::multiple($element->styles, $styles);
        return new static($output, $content, $element_styles);
    }
    /**
     * Get the string representation of the element.
     */
    public function to_string(): string
    {
        if (is_array($this->content)) {
            $inheritance = new Inherit_Styles();
            $this->content = implode('', $inheritance($this->content, $this->styles));
        }
        return $this->styles->format($this->content);
    }
    /**
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (method_exists($this->styles, $name)) {
            // @phpstan-ignore-next-line
            $result = $this->styles->{$name}(...$arguments);
            if (str_starts_with($name, 'get') || str_starts_with($name, 'has')) {
                return $result;
            }
        }
        return $this;
    }
    /**
     * Sets the content of the element.
     *
     * @param  array<int, Element|string>|string  $content
     */
    final public function set_content(array|string $content): static
    {
        return new static($this->output, $content, $this->styles);
    }
    /**
     * Renders the string representation of the element on the output.
     */
    final public function render(int $options): void
    {
        $this->output->writeln($this->to_string(), $options);
    }
    /**
     * Get the string representation of the element.
     */
    final public function __toString(): string
    {
        return $this->to_string();
    }
}