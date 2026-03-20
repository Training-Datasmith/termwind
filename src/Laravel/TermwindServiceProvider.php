<?php

declare (strict_types=1);
namespace Termwind\Laravel;

use Illuminate\Console\Output_Style;
use Illuminate\Support\Service_Provider;
use Termwind\Termwind;
final class Termwind_Service_Provider extends Service_Provider
{
    /**
     * Sets the correct renderer to be used.
     */
    public function register(): void
    {
        $this->app->resolving(Output_Style::class, function ($style): void {
            Termwind::render_using($style->get_output());
        });
    }
}