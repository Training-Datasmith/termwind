# Architecture: termwind

## Purpose

A PHP library for building styled CLI output using an HTML-like syntax. Renders HTML-subset markup to the terminal with Tailwind-inspired utility classes controlling colours, spacing, and layout.

## Directory Structure

```
src/
  Termwind.php                   - Entry point: render(), terminal(), ask()
  Html_Renderer.php              - Parses HTML string and renders components to output
  Terminal.php                   - Terminal width/height detection
  Functions.php                  - Global helper functions: render(), style(), terminal()
  Components/                    - HTML element renderers (Div, Span, Paragraph, Ul, Ol, Li, Hr, Dl, Dt, Dd, Anchor, Raw, BreakLine)
  Actions/
    Style_To_Method.php          - Maps CSS-like class names to component method calls
  Enums/
    Color.php                    - Terminal ANSI colour enum
  Html/
    Code_Renderer.php            - Renders <code> blocks with syntax highlighting
    Pre_Renderer.php             - Renders <pre> blocks preserving whitespace
    Table_Renderer.php           - Renders <table> elements as aligned terminal columns
    Inherit_Styles.php           - Propagates parent styles to child elements
  Repositories/
    Styles.php                   - Registry of named style definitions
  ValueObjects/
    Node.php                     - Parsed HTML element value object
    Style.php / Styles.php       - Terminal style value objects
  Helpers/
    Question_Helper.php          - Terminal interactive question/answer helper
  Laravel/
    Termwind_Service_Provider.php - Laravel service provider (registers global functions)
  Exceptions/                    - Domain exceptions for invalid colors/styles
```

## Key Design Decisions

- **HTML-subset syntax**: Uses familiar HTML/CSS-like markup (`<div class="mt-2 text-green">`) rather than a custom DSL, lowering the learning curve.
- **Tailwind-inspired classes**: Class names like `mt-2`, `text-red`, `font-bold` are pre-mapped to ANSI escape codes by `Style_To_Method`.
- **Component model**: Each HTML element maps to a PHP component class that handles its own rendering, making it easy to add new element types.
- **Framework-agnostic with optional Laravel integration**: Works standalone; `Termwind_Service_Provider` adds optional Laravel DI integration and registers global helper functions.

## Extension Points

- Register custom style classes via `Termwind::style('my-style', ...)`.
- Add new HTML element components by creating a class in `Components/` and registering it in `Html_Renderer`.

## Dependency Flow

```
render('<div class="text-green">Hello</div>')
  └─> Html_Renderer::render()
        └─> parse HTML into Nodes
        └─> map each Node → Component class
        └─> Style_To_Method → apply ANSI styles
        └─> output formatted string to terminal
```
