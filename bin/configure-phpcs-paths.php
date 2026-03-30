<?php
$paths = ['../../../src', '../../magento/magento-coding-standard/Magento2'];

if (is_dir(__DIR__ . '/../vendor/magento/php-compatibility-fork')) {
    $paths[] = '../../magento/php-compatibility-fork';
    $paths[] = '../../phpcsstandards/phpcsutils';
} elseif (is_dir(__DIR__ . '/../vendor/phpcompatibility/php-compatibility')) {
    $paths[] = '../../phpcompatibility/php-compatibility/PHPCompatibility';
}

passthru('vendor/bin/phpcs --config-set installed_paths ' . implode(',', $paths));
