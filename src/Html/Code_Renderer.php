<?php

declare (strict_types=1);
namespace Termwind\Html;

use Termwind\Components\Element;
use Termwind\Termwind;
use Termwind\Value_Objects\Node;
/**
 * @internal
 */
final class Code_Renderer
{
    public const TOKEN_DEFAULT = 'token_default';
    public const TOKEN_COMMENT = 'token_comment';
    public const TOKEN_STRING = 'token_string';
    public const TOKEN_HTML = 'token_html';
    public const TOKEN_KEYWORD = 'token_keyword';
    public const ACTUAL_LINE_MARK = 'actual_line_mark';
    public const LINE_NUMBER = 'line_number';
    private const ARROW_SYMBOL_UTF8 = '➜';
    private const DELIMITER_UTF8 = '▕ ';
    // '▶';
    private const LINE_NUMBER_DIVIDER = 'line_divider';
    private const MARKED_LINE_NUMBER = 'marked_line';
    private const WIDTH = 3;
    /**
     * Holds the theme.
     *
     * @var array<string, string>
     */
    private const THEME = [self::TOKEN_STRING => 'text-gray', self::TOKEN_COMMENT => 'text-gray italic', self::TOKEN_KEYWORD => 'text-magenta strong', self::TOKEN_DEFAULT => 'strong', self::TOKEN_HTML => 'text-blue strong', self::ACTUAL_LINE_MARK => 'text-red strong', self::LINE_NUMBER => 'text-gray', self::MARKED_LINE_NUMBER => 'italic strong', self::LINE_NUMBER_DIVIDER => 'text-gray'];
    private string $delimiter = self::DELIMITER_UTF8;
    private string $arrow = self::ARROW_SYMBOL_UTF8;
    private const NO_MARK = '    ';
    /**
     * Highlights HTML content from a given node and converts to the content element.
     */
    public function to_element(Node $node): \Termwind\Components\Div
    {
        $line = max((int) $node->get_attribute('line'), 0);
        $start_line = max((int) $node->get_attribute('start-line'), 1);
        $html = $node->get_html();
        $lines = explode("\n", $html);
        $extra_spaces = $this->find_extra_spaces($lines);
        if ($extra_spaces !== '') {
            $lines = array_map(static fn(string $line): string => str_starts_with($line, $extra_spaces) ? substr($line, strlen($extra_spaces)) : $line, $lines);
            $html = implode("\n", $lines);
        }
        $token_lines = $this->get_highlighted_lines(trim($html, "\n"), $start_line);
        $lines = $this->color_lines($token_lines);
        $lines = $this->line_numbers($lines, $line);
        return Termwind::div(trim($lines, "\n"));
    }
    /**
     * Finds extra spaces which should be removed from HTML.
     *
     * @param  array<int, string>  $lines
     */
    private function find_extra_spaces(array $lines): string
    {
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            if (preg_replace('/\s+/', '', $line) === '') {
                return $line;
            }
        }
        return '';
    }
    /**
     * Returns content split into lines with numbers.
     *
     * @return array<int, array<int, array{0: string, 1: non-empty-string}>>
     */
    private function get_highlighted_lines(string $source, int $start_line): array
    {
        $source = str_replace(["\r\n", "\r"], "\n", $source);
        $tokens = $this->tokenize($source);
        return $this->split_to_lines($tokens, $start_line - 1);
    }
    /**
     * Splits content into tokens.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function tokenize(string $source): array
    {
        $tokens = token_get_all($source);
        $output = [];
        $current_type = null;
        $new_type = self::TOKEN_KEYWORD;
        $buffer = '';
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[0] !== T_WHITESPACE) {
                    $new_type = match ($token[0]) {
                        T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG, T_STRING, T_VARIABLE, T_DIR, T_FILE, T_METHOD_C, T_DNUMBER, T_LNUMBER, T_NS_C, T_LINE, T_CLASS_C, T_FUNC_C, T_TRAIT_C => self::TOKEN_DEFAULT,
                        T_COMMENT, T_DOC_COMMENT => self::TOKEN_COMMENT,
                        T_ENCAPSED_AND_WHITESPACE, T_CONSTANT_ENCAPSED_STRING => self::TOKEN_STRING,
                        T_INLINE_HTML => self::TOKEN_HTML,
                        default => self::TOKEN_KEYWORD,
                    };
                }
            } else {
                $new_type = $token === '"' ? self::TOKEN_STRING : self::TOKEN_KEYWORD;
            }
            if ($current_type === null) {
                $current_type = $new_type;
            }
            if ($current_type !== $new_type) {
                $output[] = [$current_type, $buffer];
                $buffer = '';
                $current_type = $new_type;
            }
            $buffer .= is_array($token) ? $token[1] : $token;
        }
        $output[] = [$new_type, $buffer];
        return $output;
    }
    /**
     * Splits tokens into lines.
     *
     * @param  array<int, array{0: string, 1: string}>  $tokens
     * @return array<int, array<int, array{0: string, 1: non-empty-string}>>
     */
    private function split_to_lines(array $tokens, int $start_line): array
    {
        $lines = [];
        $line = [];
        foreach ($tokens as $token) {
            foreach (explode("\n", $token[1]) as $count => $token_line) {
                if ($count > 0) {
                    $lines[$start_line++] = $line;
                    $line = [];
                }
                if ($token_line === '') {
                    continue;
                }
                $line[] = [$token[0], $token_line];
            }
        }
        $lines[$start_line++] = $line;
        return $lines;
    }
    /**
     * Applies colors to tokens according to a color schema.
     *
     * @param  array<int, array<int, array{0: string, 1: non-empty-string}>>  $tokenLines
     * @return array<int, string>
     */
    private function color_lines(array $token_lines): array
    {
        $lines = [];
        foreach ($token_lines as $line_count => $token_line) {
            $line = '';
            foreach ($token_line as $token) {
                [$token_type, $token_value] = $token;
                $line .= $this->style_token($token_type, $token_value);
            }
            $lines[$line_count] = $line;
        }
        return $lines;
    }
    /**
     * Prepends line numbers into lines.
     *
     * @param  array<int, string>  $lines
     */
    private function line_numbers(array $lines, int $mark_line): string
    {
        $last_line = (int) array_key_last($lines);
        $line_length = strlen((string) ($last_line + 1));
        $line_length = $line_length < self::WIDTH ? self::WIDTH : $line_length;
        $snippet = '';
        $mark = '  ' . $this->arrow . ' ';
        foreach ($lines as $i => $line) {
            $colored_line_number = $this->colored_line_number(self::LINE_NUMBER, $i, $line_length);
            if ($mark_line !== 0) {
                $snippet .= $mark_line === $i + 1 ? $this->style_token(self::ACTUAL_LINE_MARK, $mark) : self::NO_MARK;
                $colored_line_number = $mark_line === $i + 1 ? $this->colored_line_number(self::MARKED_LINE_NUMBER, $i, $line_length) : $colored_line_number;
            }
            $snippet .= $colored_line_number;
            $snippet .= $this->style_token(self::LINE_NUMBER_DIVIDER, $this->delimiter);
            $snippet .= $line . PHP_EOL;
        }
        return $snippet;
    }
    /**
     * Formats line number and applies color according to a color schema.
     */
    private function colored_line_number(string $token, int $line_number, int $length): string
    {
        return $this->style_token($token, str_pad((string) ($line_number + 1), $length, ' ', STR_PAD_LEFT));
    }
    /**
     * Formats string and applies color according to a color schema.
     */
    private function style_token(string $token, string $string): string
    {
        return (string) Termwind::span($string, self::THEME[$token]);
    }
}