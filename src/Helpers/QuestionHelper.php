<?php

declare (strict_types=1);
namespace Termwind\Helpers;

use Symfony\Component\Console\Formatter\Output_Formatter;
use Symfony\Component\Console\Helper\Symfony_Question_Helper;
use Symfony\Component\Console\Output\Output_Interface;
use Symfony\Component\Console\Question\Question;
/**
 * @internal
 */
final class Question_Helper extends Symfony_Question_Helper
{
    /**
     * {@inheritdoc}
     */
    protected function write_prompt(Output_Interface $output, Question $question): void
    {
        $text = Output_Formatter::escape_trailing_backslash($question->get_question());
        $output->write($text);
    }
}