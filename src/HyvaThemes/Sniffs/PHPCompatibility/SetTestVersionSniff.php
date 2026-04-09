<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HyvaThemes\Sniffs\PHPCompatibility;

use HyvaThemes\Helpers\ComposerPhpVersion;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class SetTestVersionSniff implements Sniff
{
    private static bool $testVersionSet = false;

    public function register(): array
    {
        return [T_OPEN_TAG];
    }

    public function process(File $phpcsFile, $stackPtr): ?int
    {
        if (self::$testVersionSet) {
            return $phpcsFile->numTokens;
        }
        self::$testVersionSet = true;

        if (Config::getConfigData('testVersion') !== null) {
            return $phpcsFile->numTokens;
        }

        $version = ComposerPhpVersion::detect(dirname($phpcsFile->getFilename()));
        Config::setConfigData('testVersion', $version . '-', true);

        return $phpcsFile->numTokens;
    }

    public static function reset(): void
    {
        self::$testVersionSet = false;
    }
}
