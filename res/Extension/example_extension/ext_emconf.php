<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Example extension',
    'description' => 'Example extension',
    'category' => 'example',
    'version' => '1.0.0',
    'state' => 'beta',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => '',
    'author_email' => '',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'fluid_styled_content' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
