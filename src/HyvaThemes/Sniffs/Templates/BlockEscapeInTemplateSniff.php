<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2022-present. All rights reserved.
 * This product is licensed under the BSD-3-Clause license.
 * See LICENSE.txt for details.
 */

declare(strict_types=1);

namespace HyvaThemes\Sniffs\Templates;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class BlockEscapeInTemplateSniff implements Sniff
{
    public const ESCAPE_METHOD_ON_BLOCK_MSG = '$block->%1$s is deprecated, use $escaper->%1$s instead';

    private const ESCAPE_METHODS = [
        'escapeHtml',
        'escapeHtmlAttr',
        'escapeUrl',
        'escapeJs',
        'escapeCss',
        'escapeJsQuote',
        'escapeXssInUrl',
        'escapeQuote',

    ];

    public function register()
    {
        return [T_VARIABLE];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        if ($phpcsFile->getTokensAsString($stackPtr, 1) !== '$block') {
            return;
        }
        $objOpPtr = $this->getNextNonWhitespaceTokenPtr($phpcsFile, $stackPtr);
        if ($this->getTokenString($phpcsFile, $objOpPtr) !== '->') {
            return;
        }

        $methodPtr = $this->getNextNonWhitespaceTokenPtr($phpcsFile, $objOpPtr);
        $method = $this->getTokenString($phpcsFile, $methodPtr);
        if (! in_array($method, self::ESCAPE_METHODS, true)) {
            return;
        }
        $message = sprintf(self::ESCAPE_METHOD_ON_BLOCK_MSG, $method);

        if ($phpcsFile->addFixableWarning($message, $stackPtr, 'BlockEscapeMethodFound') === true) {
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($stackPtr, '$escaper');
            $phpcsFile->fixer->endChangeset();
        }
    }

    private function getNextNonWhitespaceTokenPtr($phpcsFile, int $start): int
    {
        $offset = 1;
        while (($phpcsFile->getTokens()[$start + $offset]['code'] ?? false) === T_WHITESPACE) {
            $offset++;
        }
        return $start + $offset;
    }

    private function getTokenString(File $phpcsFile, int $ptr): string
    {
        return isset($phpcsFile->getTokens()[$ptr])
            ? $phpcsFile->getTokensAsString($ptr, 1)
            : '';
    }
}
