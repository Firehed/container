<?php
declare(strict_types=1);

return [
    // Literals
    'string_literal' => 'UnitTest',
    'int_literal' => 42,
    'float_literal' => 123.45,
    'bool_literal' => true,
    'array_literal' => ['a', 'b', 'c'],
    'dict_literal' => ['a' => 1, 'b' => 2, 'c' => 3],

    // Sanity checks on "empty" literals
    'false_literal' => false,
    'null_literal' => null,
    'zero_litreal' => 0,
    'zero_float_literal' => 0.0,
    'empty_string_literal' => '',
];
