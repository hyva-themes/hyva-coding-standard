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

/**
 * Note: Spaces before colons are covered by Squiz.ControlStructures.ControlSignature.SpaceAfterCloseParenthesis
 */
class IfInTemplateSniff implements Sniff
{
    public const IF_BLOCK_WHITESPACE_BEFORE_CLOSE_TAG_MISSING = 'If block missing whitespace before closing tag';

    public function register()
    {
        return [T_IF, T_ELSE, T_ELSEIF];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        if ($phpcsFile->getTokens()[$stackPtr]['code'] === T_ELSE) {
            $closingPtr = $stackPtr;
        } else {
            $closingPtr = $phpcsFile->findNext(T_CLOSE_PARENTHESIS, $stackPtr);
            if ($closingPtr === false) {
                return;
            }
        }
        $blockStartPtr = $phpcsFile->findNext([T_COLON, T_OPEN_CURLY_BRACKET], $closingPtr);

        if ($blockStartPtr && $this->getTokenString($phpcsFile, $blockStartPtr) === ':') {
            $nextCode = $phpcsFile->getTokens()[$blockStartPtr + 1]['code'] ?? '';
            if ($nextCode === T_CLOSE_TAG) {
                $code = 'MissingWhitespaceBeforeCloseTag';
                $phpcsFile->addWarning(self::IF_BLOCK_WHITESPACE_BEFORE_CLOSE_TAG_MISSING, $stackPtr, $code);
            }
        }
    }

    private function getTokenString(File $phpcsFile, int $ptr): string
    {
        return isset($phpcsFile->getTokens()[$ptr])
            ? $phpcsFile->getTokensAsString($ptr, 1)
            : '';
    }
}
