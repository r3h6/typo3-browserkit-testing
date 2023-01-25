<?php

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config->getFinder()->in(__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/res');
return $config;
