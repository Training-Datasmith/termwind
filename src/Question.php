<?php

declare (strict_types=1);
namespace Termwind;

use ReflectionClass;
use Symfony\Component\Console\Helper\Symfony_Question_Helper;
use Symfony\Component\Console\Input\Argv_Input;
use Symfony\Component\Console\Input\Streamable_Input_Interface;
use Symfony\Component\Console\Question\Question as SymfonyQuestion;
use Symfony\Component\Console\Style\Symfony_Style;
use Termwind\Helpers\Question_Helper;
/**
 * @internal
 */
final class Question
{
    /**
     * The streamable input to receive the input from the user.
     */
    private static ?Streamable_Input_Interface $streamable_input;
    public function __construct(private readonly ?Symfony_Question_Helper $helper = new Question_Helper())
    {
    }
    /**
     * Sets the streamable input implementation.
     */
    public static function set_streamable_input(?Streamable_Input_Interface $streamable_input): void
    {
        self::$streamable_input = $streamable_input ?? new Argv_Input();
    }
    /**
     * Gets the streamable input implementation.
     */
    public static function get_streamable_input(): Streamable_Input_Interface
    {
        return self::$streamable_input ??= new Argv_Input();
    }
    /**
     * Renders a prompt to the user.
     *
     * @param  iterable<array-key, string>|null  $autocomplete
     */
    public function ask(string $question, ?iterable $autocomplete = null): mixed
    {
        $html = (new Html_Renderer())->parse($question)->to_string();
        $question = new Symfony_Question($html);
        if ($autocomplete !== null) {
            $question->set_autocompleter_values($autocomplete);
        }
        $output = Termwind::get_renderer();
        if ($output instanceof Symfony_Style) {
            $property = (new ReflectionClass(Symfony_Style::class))->get_property('questionHelper');
            $current_helper = $property->is_initialized($output) ? $property->get_value($output) : new Symfony_Question_Helper();
            $property->set_value($output, new Question_Helper());
            try {
                return $output->ask_question($question);
            } finally {
                $property->set_value($output, $current_helper);
            }
        }
        return $this->helper->ask(self::get_streamable_input(), Termwind::get_renderer(), $question);
    }
}