<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2022-present. All rights reserved.
 * This product is licensed under the BSD-3-Clause license.
 * See LICENSE.txt for details.
 */

declare(strict_types=1);

namespace HyvaThemes\Sniffs\Annotation;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class InheritsMethodAnnotationSniff implements Sniff
{
    public function register()
    {
        return [T_FUNCTION];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        $classStartPtr = $phpcsFile->findPrevious([T_CLASS, T_ANON_CLASS], $stackPtr, 0);
        $commentEndPtr = $phpcsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPtr, $classStartPtr);
        if (!$commentEndPtr) {
            // No comment block
            return;
        }

        $commentStartPtr = $phpcsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $commentEndPtr, $classStartPtr);

        for ($ptr = $commentStartPtr; $ptr < $commentEndPtr; $ptr++) {
            $token = $phpcsFile->getTokens()[$ptr];
            if ($token['code'] === T_DOC_COMMENT_TAG && strtolower($token['content']) === '@inheritdoc') {
                $this->warn($phpcsFile, $ptr);
            } elseif ($token['code'] === T_DOC_COMMENT_STRING && trim(strtolower($token['content'])) === '{@inheritdoc}') {
                $this->warn($phpcsFile, $ptr);
            }
        }
    }

    private function warn(File $phpcsFile, $ptr)
    {
        $phpcsFile->addWarning('Annotation @inheritDoc should not be used.', $ptr, 'InheritDocFound');
    }
}
