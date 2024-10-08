<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// pest()->extend(Tests\TestCase::class)->in('Feature');

pest()->group('unit')->in('Unit');
pest()->group('integration')->in('Integration');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

//expect()->extend('toBeOne', function () {
//    return $this->toBe(1);
//});

expect()->extend('toBeLinkedTo', function (string $path) {
    $this->toBeFile()->and(is_link($this->value))->toBeTrue()->and(readlink($this->value))->toBe($path);
    return $this;
});

expect()->extend('toBeLink', function () {
    $this->and(is_link($this->value))->toBeTrue();
    return $this;
});

expect()->extend('andContents', function () {
    $this->value = file_get_contents($this->value);
    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

enum FuzzType
{
    case String;
    case Int;
    case Float;
    case Bool;
    case Array;

    public function getValues(): array
    {
        return match ($this) {
            self::String => ['', random_bytes(1024), '0', 'false', '-1', 'foo'],
            self::Int => [1, 0, PHP_INT_MAX, PHP_INT_MIN],
            self::Float => [1.1, 0.0, PHP_FLOAT_MAX, PHP_FLOAT_MIN],
            self::Bool => [true, false],
            self::Array => [['foo', 'bar', 'baz', 'qux'], [0, 1, 2, 3], ['foo' => 'bar', 'baz' => 'buz']],
        };
    }
}

/**
 * @param array<string, FuzzType|array> $keys
 * @return Generator
 */
function simple_fuzz(array $keys): \Generator
{
    $testsNeeded = [];
    foreach ($keys as $key => $value) {
        $testsNeeded[$key] = $value instanceof FuzzType ? $value->getValues() : $value;
        shuffle($testsNeeded[$key]);
    }

    do {
        $case = [];
        foreach ($testsNeeded as $key => &$value) {
            $case[$key] = array_shift($value);
            if ($value === []) {
                unset($testsNeeded[$key]);
            }
        }
        unset($value);

        yield $case;
    } while ($case !== []);
}
