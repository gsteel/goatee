# Simplified Mustache/Handlebars-Like Template Renderer

This is a small template rendering library with a subset of the features that most Handlebars implementations provide.

## Install

```bash
composer require gsteel/template-string
```

## Features

- Variable interpolation
- Arbitrary function calls
- Arbitrary Filters

> [!WARNING]
> This library does **NOT** automatically escape any output. Its purpose is for small, focussed tasks that require more advanced interpolation and features in a small, easy to use/read/understand template format than hand-rolling some kind of string replacement thing. Think email message subject lines, SMS or What's app templates…

## Basic Usage

### Simple String Interpolation

```php
use GSteel\TemplateString\FilterManager;
use GSteel\TemplateString\FunctionManager;
use GSteel\TemplateString\TemplateRenderer;

$renderer = new TemplateRenderer(
    new FilterManager(),
    new FunctionManager(),
);

$person = [
    'name' => 'Granny',
    'bornOn' => 'yesterday',
];

$result = $renderer->render(
    'Hi there {{ person.name }}, Happy birthday for {{ person.bornOn }}',
    ['person' => $person],
);
```

### Whitespace

Whitespace is permitted in string literals and expressions, so the following is fine:

```php
$template = <<<'EOF'
In the morning light,

You sleep despite my {{ 
    animal.noise | uppercase
}}.

I stand on your face
EOF;
```

### Using Filters to Process Variables

```php
use GSteel\TemplateString\Context;
use GSteel\TemplateString\FilterManager;
use GSteel\TemplateString\FunctionManager;
use GSteel\TemplateString\TemplateRenderer;

$renderer = new TemplateRenderer(
    new FilterManager([
        'dateFormat' => static function (mixed $input, Context $context): string {
            if (! $input instanceof DateTimeInterface) {
                return ''; // Wtf? Expected a date
            }
            
            $format = $context->extract('config.format') ?? 'jS F Y';
            assert(is_string($format));
            
            return $input->format($format);
        };
    ]),
    new FunctionManager(),
);

$person = [
    'name' => 'Jane',
    'bornOn' => DateTimeImmutable::createFromFormat('!Y-m-d', '2020-01-01'),
];

$config = [
    'format' => 'Y-m-d',
];

$result = $renderer->render(
    'Hi there {{ person.name }}, your birthdate is {{ person.bornOn | dateFormat }}',
    [
        'person' => $person,
        'config' => $config,
    ],
);
assert($result === 'Hi there Jane, your birthdate is 2020-01-01');
```

### Interpolating Arbitrary Function Output

```php
use GSteel\TemplateString\Context;
use GSteel\TemplateString\FilterManager;
use GSteel\TemplateString\FunctionManager;
use GSteel\TemplateString\TemplateRenderer;

$renderer = new TemplateRenderer(
    new FilterManager(),
    new FunctionManager([
        'concat' => function (mixed $left, mixed $right): string {
            $left = is_string($left) ? $left : '';
            $right = is_string($right) ? $right : '';
            
            return sprintf('%s %s', $left, $right);
        },
    ]),
);

$model = [
    'foo' => 'Bar',
    'bar' => 'Foo',
];

$result = $renderer->render(
    'Hi there {{ concat(bar, foo) }}',
    $model,
);

assert($result === 'Hi there Foo Bar');
```

### Type Safety and Signatures of Functions and Filters

There is no type safety! Assume everything is `mixed`. The main target for this library is for situations where your data/model is well typed upfront, and you are exercising complete control over the closures you are making available to templates.

#### Filter Signature

Filters are chainable and are processed left-to-right. They receive exactly 2 arguments, the output from a model variable, or previous function, and the "Context", which is a small wrapper around the entire data set given to the main renderer.

You do not have to use the context, but your callable will be provided it regardless.

```php
use GSteel\TemplateString\Context;

$filter = function (mixed $input, Context $context): mixed { /** Closure Body */ }
```

You can return anything you like from a filter, but if it isn't a `scalar`, or something that can be cast to a `string` it'll probably get ignored, unless your template chains the output to another filter that can turn the item into a `string`.

> [!WARNING]
> As filters can be any callable, don't be tempted to register things like `[strrev => strrev(...)]`. In this case, you would need to wrap `strrev` in a closure or PHP will complain about the "Context" being passed as a 2nd argument. This goes for string-based callables too.

#### Function Signature

Functions are effectively `callable(mixed...): mixed`. Again, return anything you like from a function, but be prepared that the template will need to pipe the function output to a filter that can coerce it to something "stringable".

A function such as `fn (): DateTimeImmutable => new DateTimeImmutable()` will yield `''` in a template such as `'{{ now() }}'`.

#### Invokable Classes

Both filters and functions can happily be invokable classes, so you can register them with the respective 'manager' by pulling them from your DI container if they have service dependencies you need. 

### Models/Data

The data model type accepted by the renderer's `render()` method is `array<array-key, mixed>|object`.
Any associative array, list or object will do.
For object arguments, only public **properties** are read. No methods are called. In the age of `readonly` classes, deferring to methods seems unnecessary.

Models are wrapped in a small class `Context` which has a single method: `extract(string): mixed`. Values are retrieved using dot notation, so an object such as:

```php
use GSteel\TemplateString\Context;

$model = new readonly class {
    public string $foo = 'bar',
    public array $baz => [
        'bing' => 'bong',
    ];
};

$context = new Context(['foo' => $model]);

$context->extract('foo.foo'); // 'bar'
$context->extract('foo.baz.bing'); // 'bong'
```

### Error Handling

The template renderer wraps all possible exceptions with `RenderingFailed`, so in general use:

```php
use GSteel\TemplateString\RenderingFailed;
use GSteel\TemplateString\TemplateRenderer;

assert($renderer instanceof TemplateRenderer);

try {
    $renderer->render('Hello {{ person.name }}');
} catch (RenderingFailed $e) {
    // Do something with $e
}
```

If you want to check templates for basic soundness, you can parse the template and extract information from the exception:

```php
use GSteel\TemplateString\SourceError;
use GSteel\TemplateString\TemplateRenderer;

assert($renderer instanceof TemplateRenderer);

try {
    $renderer->parseTemplate('Hi {{ person.name }')
} catch (SourceError $error) {
    $offendingLine = $error->sourceLine;
    $offendingColumn = $error->sourceColumn;
    $errorMessage = $error->getMessage();
}
```

The parser(s) are not tolerant, so you'll only get 1 error at a time.

## Contributions

Contributions are welcome, but please no bots.
Fuck AI and the horse it rode in on.
I don't want this to get bloated, but it's unlikely it will be used by anyone anyway 😂.

As always, CI needs to pass and tests are required 👍
