<?php
use PhpCsFixer\Finder;
use PhpCsFixer\Config;

$directories = ['storage', 'assets', 'vendor'];
$files = ['*.blade.php', '*.bc.php', '_ide_helper.php', '*.phar'];

$rules = [
    '@PSR2' => true,
    'array_syntax' => ['syntax' => 'short'],
    'binary_operator_spaces' => ['align_equals' => false, 'align_double_arrow' => false],
    'class_keyword_remove' => true,
    'cast_spaces' => true,
    'combine_consecutive_unsets' => true,
    'concat_space' => ['spacing' => 'none'],
    'dir_constant' => true,
    'elseif' => true,
    'explicit_indirect_variable' => true,
    'full_opening_tag' => true,
    'hash_to_slash_comment' => true,
    'heredoc_to_nowdoc' => true,
    'include' => true,
    'linebreak_after_opening_tag' => true,
    'list_syntax' => ['syntax' => 'long'],
    'lowercase_cast' => true,
    'lowercase_constants' => true,
    'lowercase_keywords' => true,
    'method_argument_space' => ['ensure_fully_multiline' => false],
    'method_chaining_indentation' => true,
    'method_separation' => true,
    'modernize_types_casting' => true,
    'normalize_index_brace' => true,
    'new_with_braces' => true,
    'no_blank_lines_after_class_opening' => true,
    'no_blank_lines_after_phpdoc' => true,
    'single_blank_line_before_namespace' => false,
    'no_blank_lines_after_class_opening' => true,
    'no_blank_lines_after_phpdoc' => true,
    'no_break_comment' => false,
    'no_closing_tag' => true,
    'no_empty_comment' => true,
    'no_empty_statement' => true,
    'no_extra_consecutive_blank_lines' => true,
    'no_leading_import_slash' => true,
    'no_mixed_echo_print' => ['use' => 'echo'],
    'no_null_property_initialization' => true,
    'no_short_echo_tag' => true,
    'no_spaces_around_offset' => true,
    'no_spaces_inside_parenthesis' => true,
    'no_trailing_comma_in_singleline_array' => true,
    'no_trailing_whitespace' => true,
    'no_trailing_whitespace_in_comment' => true,
    'no_unused_imports' => true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'no_whitespace_in_blank_line' => true,
    'no_whitespace_before_comma_in_array' => true,
    'no_whitespace_in_blank_line' => true,
    'not_operator_with_successor_space' => true,
    'phpdoc_align' => true,
    'phpdoc_indent' => true,
    'phpdoc_order' => true,
    'phpdoc_separation' => true,
    'phpdoc_single_line_var_spacing' => true,
    'phpdoc_summary' => true,
    'phpdoc_to_comment' => true,
    'phpdoc_trim' => true,
    'phpdoc_var_without_name' => true,
    'semicolon_after_instruction' => true,
    'short_scalar_cast' => true,
    'simplified_null_return' => true,
    'single_blank_line_at_eof' => true,
    'single_import_per_statement' => true,
    'single_quote' => true,
    'standardize_not_equals' => true,
    'switch_case_space' => false,
    'ternary_operator_spaces' => true,
    'ternary_to_null_coalescing' => false,
    'trailing_comma_in_multiline_array' => true,
    'trim_array_spaces' => true,
    'unary_operator_spaces' => true,
];

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude($directories);

foreach ($files as $file) {
    $finder->notName($file);
}

return Config::create()
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setUsingCache(false)
    ->setFinder($finder);
