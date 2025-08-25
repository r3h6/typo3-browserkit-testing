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
            'typo3' => '11.5.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
