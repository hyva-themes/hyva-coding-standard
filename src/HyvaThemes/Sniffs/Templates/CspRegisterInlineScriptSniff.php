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

class CspRegisterInlineScriptSniff implements Sniff
{
    public const MSG_MISSING_CSP_CALL = 'Missing <?php $hyvaCsp->registerInlineScript(); ?> after </script> tag';

    public const MSG_MISSING_CSP_CALL_WITH_ISSET = 'Missing <?php if (isset($hyvaCsp)) $hyvaCsp->registerInlineScript(); ?> after </script> tag in base area template';

    public const MSG_UNEXPECTED_CSP_CALL = '$hyvaCsp->registerInlineScript() must not be used in adminhtml area templates';

    public const MSG_MISSING_USE_IMPORT = 'Template with <script> tags must have: use Hyva\\Theme\\ViewModel\\HyvaCsp;';

    public const MSG_MISSING_VAR_ANNOTATION = 'Template with <script> tags must have: /** @var HyvaCsp $hyvaCsp */';

    private const AREA_FRONTEND = 'frontend';
    private const AREA_BASE = 'base';
    private const AREA_ADMINHTML = 'adminhtml';

    /** @var array<string, bool> */
    private $checkedFiles = [];

    public function register()
    {
        return [T_INLINE_HTML];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        $content = $phpcsFile->getTokensAsString($stackPtr, 1);

        if (stripos($content, '</script>') === false) {
            return;
        }

        $area = $this->detectArea($phpcsFile);
        $filename = $phpcsFile->getFilename();

        if ($area === self::AREA_ADMINHTML) {
            if (! isset($this->checkedFiles[$filename])) {
                $this->checkedFiles[$filename] = true;
                $this->checkAdminhtmlHasNoRegisterCall($phpcsFile);
            }
            return;
        }

        if (! isset($this->checkedFiles[$filename])) {
            $this->checkedFiles[$filename] = true;
            $this->checkUseImport($phpcsFile, $stackPtr);
            $this->checkVarAnnotation($phpcsFile, $stackPtr);
        }

        $this->checkRegisterInlineScriptFollows($phpcsFile, $stackPtr, $content, $area);
    }

    private function detectArea(File $phpcsFile): string
    {
        $path = $phpcsFile->getFilename();
        if (strpos($path, 'view/adminhtml/') !== false) {
            return self::AREA_ADMINHTML;
        }
        if (strpos($path, 'view/base/') !== false) {
            return self::AREA_BASE;
        }
        return self::AREA_FRONTEND;
    }

    private function checkRegisterInlineScriptFollows(
        File $phpcsFile,
        int $stackPtr,
        string $content,
        string $area
    ): void {
        $scriptCloseCount = substr_count(strtolower($content), '</script>');

        // Multiple </script> in one HTML block means at least some are missing CSP calls
        if ($scriptCloseCount > 1) {
            for ($i = 0; $i < $scriptCloseCount - 1; $i++) {
                $this->addMissingCspWarning($phpcsFile, $stackPtr, $area);
            }
        }

        // Check content after the last </script> is whitespace-only
        $lastPos = strripos($content, '</script>');
        $afterScript = substr($content, $lastPos + 9);
        if (trim($afterScript) !== '') {
            $this->addMissingCspWarning($phpcsFile, $stackPtr, $area);
            return;
        }

        // Walk forward to find the next PHP open tag
        $tokens = $phpcsFile->getTokens();
        $nextPtr = $stackPtr + 1;

        while (isset($tokens[$nextPtr]) && $tokens[$nextPtr]['code'] === T_INLINE_HTML) {
            if (trim($tokens[$nextPtr]['content']) !== '') {
                $this->addMissingCspWarning($phpcsFile, $stackPtr, $area);
                return;
            }
            $nextPtr++;
        }

        if (! isset($tokens[$nextPtr]) || $tokens[$nextPtr]['code'] !== T_OPEN_TAG) {
            $this->addMissingCspWarning($phpcsFile, $stackPtr, $area);
            return;
        }

        // Collect PHP code between open and close tags
        $phpCode = '';
        $nextPtr++;
        while (isset($tokens[$nextPtr]) && $tokens[$nextPtr]['code'] !== T_CLOSE_TAG) {
            $phpCode .= $tokens[$nextPtr]['content'];
            $nextPtr++;
        }

        // Normalize: strip all whitespace for comparison
        $normalizedCode = preg_replace('/\s+/', '', trim($phpCode));

        if ($area === self::AREA_FRONTEND) {
            if ($normalizedCode !== '$hyvaCsp->registerInlineScript();') {
                $this->addMissingCspWarning($phpcsFile, $stackPtr, $area);
            }
        } elseif ($area === self::AREA_BASE) {
            if ($normalizedCode !== 'if(isset($hyvaCsp))$hyvaCsp->registerInlineScript();') {
                $this->addMissingCspWarning($phpcsFile, $stackPtr, $area);
            }
        }
    }

    private function addMissingCspWarning(File $phpcsFile, int $stackPtr, string $area): void
    {
        if ($area === self::AREA_BASE) {
            $phpcsFile->addWarning(self::MSG_MISSING_CSP_CALL_WITH_ISSET, $stackPtr, 'MissingCspRegisterInlineScriptWithIsset');
        } else {
            $phpcsFile->addWarning(self::MSG_MISSING_CSP_CALL, $stackPtr, 'MissingCspRegisterInlineScript');
        }
    }

    private function checkAdminhtmlHasNoRegisterCall(File $phpcsFile): void
    {
        $tokens = $phpcsFile->getTokens();
        foreach ($tokens as $ptr => $token) {
            if ($token['code'] === T_STRING && $token['content'] === 'registerInlineScript') {
                $phpcsFile->addWarning(self::MSG_UNEXPECTED_CSP_CALL, $ptr, 'UnexpectedCspRegisterInlineScript');
            }
        }
    }

    private function checkUseImport(File $phpcsFile, int $reportPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        foreach ($tokens as $ptr => $token) {
            if ($token['code'] !== T_USE) {
                continue;
            }

            // Skip closure use (preceded by close parenthesis)
            $prevPtr = $phpcsFile->findPrevious(T_WHITESPACE, $ptr - 1, null, true);
            if ($prevPtr !== false && $tokens[$prevPtr]['code'] === T_CLOSE_PARENTHESIS) {
                continue;
            }

            // Collect use statement content (skip whitespace)
            $useContent = '';
            $lookPtr = $ptr + 1;
            while (isset($tokens[$lookPtr]) && $tokens[$lookPtr]['code'] !== T_SEMICOLON) {
                if ($tokens[$lookPtr]['code'] !== T_WHITESPACE) {
                    $useContent .= $tokens[$lookPtr]['content'];
                }
                $lookPtr++;
            }

            if ($useContent === 'Hyva\Theme\ViewModel\HyvaCsp') {
                return;
            }
        }

        $phpcsFile->addWarning(self::MSG_MISSING_USE_IMPORT, $reportPtr, 'MissingHyvaCspUseImport');
    }

    private function checkVarAnnotation(File $phpcsFile, int $reportPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        foreach ($tokens as $token) {
            if ($token['code'] === T_DOC_COMMENT_STRING && strpos($token['content'], 'HyvaCsp') !== false) {
                return;
            }
        }

        $phpcsFile->addWarning(self::MSG_MISSING_VAR_ANNOTATION, $reportPtr, 'MissingHyvaCspAnnotation');
    }
}
