<?php

declare (strict_types=1);
namespace Termwind\Value_Objects;

use Generator;
/**
 * @internal
 */
final readonly class Node implements \Stringable
{
    /**
     * A value object with helper methods for working with DOM node.
     */
    public function __construct(private \Dom_Node $node)
    {
    }
    /**
     * Gets the value of the node.
     */
    public function get_value(): string
    {
        return $this->node->node_value ?? '';
    }
    /**
     * Gets child nodes of the node.
     *
     * @return Generator<Node>
     */
    public function get_child_nodes(): Generator
    {
        foreach ($this->node->child_nodes as $node) {
            yield new self($node);
        }
    }
    /**
     * Checks if the node is a text.
     */
    public function is_text(): bool
    {
        return $this->node instanceof \Dom_Text;
    }
    /**
     * Checks if the node is a comment.
     */
    public function is_comment(): bool
    {
        return $this->node instanceof \Dom_Comment;
    }
    /**
     * Compares the current node name with a given name.
     */
    public function is_name(string $name): bool
    {
        return $this->get_name() === $name;
    }
    /**
     * Returns the current node type name.
     */
    public function get_name(): string
    {
        return $this->node->node_name;
    }
    /**
     * Returns value of [class] attribute.
     */
    public function get_class_attribute(): string
    {
        return $this->get_attribute('class');
    }
    /**
     * Returns value of attribute with a given name.
     */
    public function get_attribute(string $name): string
    {
        if ($this->node instanceof \Dom_Element) {
            return $this->node->get_attribute($name);
        }
        return '';
    }
    /**
     * Checks if the node is empty.
     */
    public function is_empty(): bool
    {
        return $this->is_text() && preg_replace('/\s+/', '', $this->get_value()) === '';
    }
    /**
     * Gets the previous sibling from the node.
     */
    public function get_previous_sibling(): ?static
    {
        $node = $this->node;
        while ($node = $node->previous_sibling) {
            $node = new self($node);
            if ($node->is_empty()) {
                $node = $node->node;
                continue;
            }
            if (!$node->is_comment()) {
                return $node;
            }
            $node = $node->node;
        }
        return is_null($node) ? null : new self($node);
    }
    /**
     * Gets the next sibling from the node.
     */
    public function get_next_sibling(): ?static
    {
        $node = $this->node;
        while ($node = $node->next_sibling) {
            $node = new self($node);
            if ($node->is_empty()) {
                $node = $node->node;
                continue;
            }
            if (!$node->is_comment()) {
                return $node;
            }
            $node = $node->node;
        }
        return is_null($node) ? null : new self($node);
    }
    /**
     * Checks if the node is the first child.
     */
    public function is_first_child(): bool
    {
        return is_null($this->get_previous_sibling());
    }
    /**
     * Gets the inner HTML representation of the node including child nodes.
     */
    public function get_html(): string
    {
        $html = '';
        foreach ($this->node->child_nodes as $child) {
            if ($child->owner_document instanceof \Dom_Document) {
                $html .= $child->owner_document->save_xml($child);
            }
        }
        return html_entity_decode($html);
    }
    /**
     * Converts the node to a string.
     */
    public function __toString(): string
    {
        if ($this->is_comment()) {
            return '';
        }
        if ($this->get_value() === ' ') {
            return ' ';
        }
        if ($this->is_empty()) {
            return '';
        }
        $text = preg_replace('/\s+/', ' ', $this->get_value()) ?? '';
        if (is_null($this->get_previous_sibling())) {
            $text = ltrim($text);
        }
        if (is_null($this->get_next_sibling())) {
            return rtrim($text);
        }
        return $text;
    }
}