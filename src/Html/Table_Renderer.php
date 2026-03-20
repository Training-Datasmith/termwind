<?php

declare (strict_types=1);
namespace Termwind\Html;

use Iterator;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\Table_Cell;
use Symfony\Component\Console\Helper\Table_Cell_Style;
use Symfony\Component\Console\Helper\Table_Separator;
use Symfony\Component\Console\Output\Buffered_Output;
use Symfony\Component\Console\Output\Output_Interface;
use Termwind\Components\Element;
use Termwind\Html_Renderer;
use Termwind\Termwind;
use Termwind\Value_Objects\Node;
use Termwind\Value_Objects\Styles;
/**
 * @internal
 */
final readonly class Table_Renderer
{
    /**
     * Symfony table object uses for table generation.
     */
    private Table $table;
    /**
     * This object is used for accumulating output data from Symfony table object and return it as a string.
     */
    private Buffered_Output $output;
    public function __construct()
    {
        $this->output = new Buffered_Output(
            // Content should output as is, without changes
            Output_Interface::VERBOSITY_NORMAL | Output_Interface::OUTPUT_RAW,
            true
        );
        $this->table = new Table($this->output);
    }
    /**
     * Converts table output to the content element.
     */
    public function to_element(Node $node): \Termwind\Components\Div
    {
        $this->parse_table($node);
        $this->table->render();
        $content = preg_replace('/\n$/', '', $this->output->fetch()) ?? '';
        return Termwind::div($content, '', ['isFirstChild' => $node->is_first_child()]);
    }
    /**
     * Looks for thead, tfoot, tbody, tr elements in a given DOM and appends rows from them to the Symfony table object.
     */
    private function parse_table(Node $node): void
    {
        $style = $node->get_attribute('style');
        if ($style !== '') {
            $this->table->set_style($style);
        }
        foreach ($node->get_child_nodes() as $child) {
            match ($child->get_name()) {
                'thead' => $this->parse_header($child),
                'tfoot' => $this->parse_foot($child),
                'tbody' => $this->parse_body($child),
                default => $this->parse_rows($child),
            };
        }
    }
    /**
     * Looks for table header title and tr elements in a given thead DOM node and adds them to the Symfony table object.
     */
    private function parse_header(Node $node): void
    {
        $title = $node->get_attribute('title');
        if ($title !== '') {
            $this->table->get_style()->set_header_title_format($this->parse_title_style($node));
            $this->table->set_header_title($title);
        }
        foreach ($node->get_child_nodes() as $child) {
            if ($child->is_name('tr')) {
                foreach ($this->parse_row($child) as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $this->table->set_headers($row);
                }
            }
        }
    }
    /**
     * Looks for table footer and tr elements in a given tfoot DOM node and adds them to the Symfony table object.
     */
    private function parse_foot(Node $node): void
    {
        $title = $node->get_attribute('title');
        if ($title !== '') {
            $this->table->get_style()->set_footer_title_format($this->parse_title_style($node));
            $this->table->set_footer_title($title);
        }
        foreach ($node->get_child_nodes() as $child) {
            if ($child->is_name('tr')) {
                $rows = iterator_to_array($this->parse_row($child));
                if (count($rows) > 0) {
                    $this->table->add_row(new Table_Separator());
                    $this->table->add_rows($rows);
                }
            }
        }
    }
    /**
     * Looks for tr elements in a given DOM node and adds them to the Symfony table object.
     */
    private function parse_body(Node $node): void
    {
        foreach ($node->get_child_nodes() as $child) {
            if ($child->is_name('tr')) {
                $this->parse_rows($child);
            }
        }
    }
    /**
     * Parses table tr elements.
     */
    private function parse_rows(Node $node): void
    {
        foreach ($this->parse_row($node) as $row) {
            $this->table->add_row($row);
        }
    }
    /**
     * Looks for th, td elements in a given DOM node and converts them to a table cells.
     *
     * @return Iterator<array<int, TableCell>|TableSeparator>
     */
    private function parse_row(Node $node): Iterator
    {
        $row = [];
        foreach ($node->get_child_nodes() as $child) {
            if ($child->is_name('th') || $child->is_name('td')) {
                $align = $child->get_attribute('align');
                $class = $child->get_class_attribute();
                if ($child->is_name('th')) {
                    $class .= ' strong';
                }
                $text = (string) (new Html_Renderer())->parse(trim(preg_replace('/<br\s?+\/?>/', "\n", $child->get_html()) ?? ''));
                if ((bool) preg_match(Styles::STYLING_REGEX, $text)) {
                    $class .= ' font-normal';
                }
                $row[] = new Table_Cell(
                    // I need only spaces after applying margin, padding and width except tags.
                    // There is no place for tags, they broke cell formatting.
                    (string) Termwind::span($text, $class),
                    [
                        // Gets rowspan and colspan from tr and td tag attributes
                        'colspan' => max((int) $child->get_attribute('colspan'), 1),
                        'rowspan' => max((int) $child->get_attribute('rowspan'), 1),
                        // There are background and foreground and options
                        'style' => $this->parse_cell_style($class, $align === '' ? Table_Cell_Style::DEFAULT_ALIGN : $align),
                    ]
                );
            }
        }
        if ($row !== []) {
            yield $row;
        }
        $border = (int) $node->get_attribute('border');
        for ($i = $border; $i--; $i > 0) {
            yield new Table_Separator();
        }
    }
    /**
     * Parses tr, td tag class attribute and passes bg, fg and options to a table cell style.
     */
    private function parse_cell_style(string $styles, string $align = Table_Cell_Style::DEFAULT_ALIGN): Table_Cell_Style
    {
        // I use this empty span for getting styles for bg, fg and options
        // It will be a good idea to get properties without element object and then pass them to an element object
        $element = Termwind::span('%s', $styles);
        $styles = [];
        $colors = $element->get_properties()['colors'] ?? [];
        foreach ($colors as $option => $content) {
            if (in_array($option, ['fg', 'bg'], true)) {
                $content = is_array($content) ? array_pop($content) : $content;
                $styles[] = "{$option}={$content}";
            }
        }
        // If there are no styles we don't need extra tags
        if ($styles === []) {
            $cell_format = '%s';
        } else {
            $cell_format = '<' . implode(';', $styles) . '>%s</>';
        }
        return new Table_Cell_Style(['align' => $align, 'cellFormat' => $cell_format]);
    }
    /**
     * Get styled representation of title.
     */
    private function parse_title_style(Node $node): string
    {
        return (string) Termwind::span(' %s ', $node->get_class_attribute());
    }
}