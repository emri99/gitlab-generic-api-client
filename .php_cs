<?php
$header = <<<'EOF'
This file is part of emri99/gitlab-generic-api-client.

(c) 2017 Cyril MERY <mery.cyril@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

return PhpCsFixer\Config::create()
    ->setHideProgress(false)
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        // '@PHP56Migration' => true,       // to prepare PHP56 migration
        'array_syntax' => ['syntax' => 'long'],
        'combine_consecutive_unsets' => true,
        'header_comment' => ['header' => $header],
        'heredoc_to_nowdoc' => true,
        'no_extra_consecutive_blank_lines' => [
            'break',
            'continue',
            'extra',
            'return',
            'throw',
            'use',
            'parenthesis_brace_block',
            'square_brace_block',
            'curly_brace_block'
        ],
        'no_short_echo_tag' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'php_unit_test_class_requires_covers' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
        'semicolon_after_instruction' => true,
    ])
    ->setFinder(
        // by default, is excluded:
        //   * .gitignore content (as others vcs ignore file)
        //   * vendor folder
        //   * dot files
        PhpCsFixer\Finder::create()
            ->exclude('tests/Fixtures')
            ->in(__DIR__)
    )
;
