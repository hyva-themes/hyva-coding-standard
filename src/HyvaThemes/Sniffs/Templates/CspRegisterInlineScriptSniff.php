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

    /** @var array<string, string> */
    private $fileAreas = [];

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
        $filename = $phpcsFile->getFilename();
        if (! isset($this->fileAreas[$filename])) {
            if (strpos($filename, 'view/adminhtml/') !== false) {
                $this->fileAreas[$filename] = self::AREA_ADMINHTML;
            } elseif (strpos($filename, 'view/base/') !== false) {
                $this->fileAreas[$filename] = self::AREA_BASE;
            } else {
                $this->fileAreas[$filename] = self::AREA_FRONTEND;
            }
        }
        return $this->fileAreas[$filename];
    }

    private function checkRegisterInlineScriptFollows(
        File $phpcsFile,
        int $stackPtr,
        string $content,
        string $area
    ): void {
        $cspSnippet = $this->getCspSnippet($area);
        $scriptCloseCount = substr_count(strtolower($content), '</script>');
        $fixIntermediates = false;
        $fixLast = false;

        // Multiple </script> in one HTML block means at least some are missing CSP calls
        if ($scriptCloseCount > 1) {
            for ($i = 0; $i < $scriptCloseCount - 1; $i++) {
                if ($this->addFixableCspWarning($phpcsFile, $stackPtr, $area)) {
                    $fixIntermediates = true;
                }
            }
        }

        // Check content after the last </script> is whitespace-only
        $lastPos = strripos($content, '</script>');
        $afterScript = substr($content, $lastPos + 9);
        if (trim($afterScript) !== '') {
            if ($this->addFixableCspWarning($phpcsFile, $stackPtr, $area)) {
                $fixLast = true;
            }
            $this->applyCspFix($phpcsFile, $stackPtr, $content, $cspSnippet, $scriptCloseCount, $fixIntermediates, $fixLast);
            return;
        }

        // Walk forward to find the next PHP open tag
        $tokens = $phpcsFile->getTokens();
        $nextPtr = $stackPtr + 1;

        while (isset($tokens[$nextPtr]) && $tokens[$nextPtr]['code'] === T_INLINE_HTML) {
            if (trim($tokens[$nextPtr]['content']) !== '') {
                if ($this->addFixableCspWarning($phpcsFile, $stackPtr, $area)) {
                    $fixLast = true;
                }
                $this->applyCspFix($phpcsFile, $stackPtr, $content, $cspSnippet, $scriptCloseCount, $fixIntermediates, $fixLast);
                return;
            }
            $nextPtr++;
        }

        if (! isset($tokens[$nextPtr]) || $tokens[$nextPtr]['code'] !== T_OPEN_TAG) {
            if ($this->addFixableCspWarning($phpcsFile, $stackPtr, $area)) {
                $fixLast = true;
            }
            $this->applyCspFix($phpcsFile, $stackPtr, $content, $cspSnippet, $scriptCloseCount, $fixIntermediates, $fixLast);
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
            $validFrontendCalls = [
                '$hyvaCsp->registerInlineScript()',
                '$hyvaCsp->registerInlineScript();',
            ];
            if (! in_array($normalizedCode, $validFrontendCalls, true)) {
                $this->addMissingCspWarning($phpcsFile, $stackPtr, $area);
            }
        } elseif ($area === self::AREA_BASE) {
            $validBaseCalls = [
                'if(isset($hyvaCsp))$hyvaCsp->registerInlineScript()',
                'if(isset($hyvaCsp))$hyvaCsp->registerInlineScript();',
                'if(isset($hyvaCsp)){$hyvaCsp->registerInlineScript()}',
                'if(isset($hyvaCsp)){$hyvaCsp->registerInlineScript();}',
            ];
            if (! in_array($normalizedCode, $validBaseCalls, true)) {
                $this->addMissingCspWarning($phpcsFile, $stackPtr, $area);
            }
        }

        // Fix intermediate scripts even if last script is correct
        $this->applyCspFix($phpcsFile, $stackPtr, $content, $cspSnippet, $scriptCloseCount, $fixIntermediates, false);
    }

    private function getCspSnippet(string $area): string
    {
        if ($area === self::AREA_BASE) {
            return '<?php if (isset($hyvaCsp)) $hyvaCsp->registerInlineScript(); ?>';
        }
        return '<?php $hyvaCsp->registerInlineScript(); ?>';
    }

    private function addFixableCspWarning(File $phpcsFile, int $stackPtr, string $area): bool
    {
        if ($area === self::AREA_BASE) {
            return $phpcsFile->addFixableWarning(self::MSG_MISSING_CSP_CALL_WITH_ISSET, $stackPtr, 'MissingCspRegisterInlineScriptWithIsset');
        }
        return $phpcsFile->addFixableWarning(self::MSG_MISSING_CSP_CALL, $stackPtr, 'MissingCspRegisterInlineScript');
    }

    private function addMissingCspWarning(File $phpcsFile, int $stackPtr, string $area): void
    {
        if ($area === self::AREA_BASE) {
            $phpcsFile->addWarning(self::MSG_MISSING_CSP_CALL_WITH_ISSET, $stackPtr, 'MissingCspRegisterInlineScriptWithIsset');
        } else {
            $phpcsFile->addWarning(self::MSG_MISSING_CSP_CALL, $stackPtr, 'MissingCspRegisterInlineScript');
        }
    }

    private function applyCspFix(
        File $phpcsFile,
        int $stackPtr,
        string $content,
        string $cspSnippet,
        int $totalScripts,
        bool $fixIntermediates,
        bool $fixLast
    ): void {
        if (! $fixIntermediates && ! $fixLast) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        $newContent = '';
        $offset = 0;

        for ($i = 1; $i <= $totalScripts; $i++) {
            $pos = stripos($content, '</script>', $offset);
            $end = $pos + 9;
            $newContent .= substr($content, $offset, $end - $offset);

            $isLast = ($i === $totalScripts);
            if ((! $isLast && $fixIntermediates) || ($isLast && $fixLast)) {
                $newContent .= "\n" . $cspSnippet;
            }

            $offset = $end;
        }

        $newContent .= substr($content, $offset);

        $phpcsFile->fixer->replaceToken($stackPtr, $newContent);
        $phpcsFile->fixer->endChangeset();
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
        if ($this->hasHyvaCspUseImport($phpcsFile)) {
            return;
        }

        $lastUseSemicolon = $this->findLastUseSemicolon($phpcsFile);

        if ($phpcsFile->addFixableWarning(self::MSG_MISSING_USE_IMPORT, $reportPtr, 'MissingHyvaCspUseImport')) {
            $phpcsFile->fixer->beginChangeset();
            if ($lastUseSemicolon !== null) {
                $phpcsFile->fixer->addContent($lastUseSemicolon, "\nuse Hyva\\Theme\\ViewModel\\HyvaCsp;");
            } else {
                // No use statements exist - insert after the first open tag
                $openTag = $phpcsFile->findNext(T_OPEN_TAG, 0);
                if ($openTag !== false) {
                    $insertAfter = $this->findEndOfLicenseComment($phpcsFile, $openTag);
                    $phpcsFile->fixer->addContent($insertAfter, "\n\nuse Hyva\\Theme\\ViewModel\\HyvaCsp;");
                }
            }
            $phpcsFile->fixer->endChangeset();
        }
    }

    private function hasHyvaCspUseImport(File $phpcsFile): bool
    {
        $tokens = $phpcsFile->getTokens();

        foreach ($tokens as $ptr => $token) {
            if ($token['code'] !== T_USE) {
                continue;
            }

            $prevPtr = $phpcsFile->findPrevious(T_WHITESPACE, $ptr - 1, null, true);
            if ($prevPtr !== false && $tokens[$prevPtr]['code'] === T_CLOSE_PARENTHESIS) {
                continue;
            }

            $useContent = '';
            $lookPtr = $ptr + 1;
            while (isset($tokens[$lookPtr]) && $tokens[$lookPtr]['code'] !== T_SEMICOLON) {
                if ($tokens[$lookPtr]['code'] !== T_WHITESPACE) {
                    $useContent .= $tokens[$lookPtr]['content'];
                }
                $lookPtr++;
            }

            if ($useContent === 'Hyva\Theme\ViewModel\HyvaCsp') {
                return true;
            }
        }

        return false;
    }

    private function checkVarAnnotation(File $phpcsFile, int $reportPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $lastVarCommentClose = null;

        foreach ($tokens as $ptr => $token) {
            if ($token['code'] === T_DOC_COMMENT_STRING && strpos($token['content'], 'HyvaCsp') !== false) {
                return;
            }
            if ($token['code'] === T_DOC_COMMENT_TAG && $token['content'] === '@var') {
                $closePtr = $phpcsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, $ptr + 1);
                if ($closePtr !== false) {
                    $lastVarCommentClose = $closePtr;
                }
            }
        }

        if ($phpcsFile->addFixableWarning(self::MSG_MISSING_VAR_ANNOTATION, $reportPtr, 'MissingHyvaCspAnnotation')) {
            $phpcsFile->fixer->beginChangeset();
            if ($lastVarCommentClose !== null) {
                $phpcsFile->fixer->addContent($lastVarCommentClose, "\n/** @var HyvaCsp \$hyvaCsp */");
            } else {
                // No @var annotations - insert after last use statement
                $lastUseSemicolon = $this->findLastUseSemicolon($phpcsFile);
                if ($lastUseSemicolon !== null) {
                    $phpcsFile->fixer->addContent($lastUseSemicolon, "\n\n/** @var HyvaCsp \$hyvaCsp */");
                }
            }
            $phpcsFile->fixer->endChangeset();
        }
    }

    private function findEndOfLicenseComment(File $phpcsFile, int $openTagPtr): int
    {
        $tokens = $phpcsFile->getTokens();
        $ptr = $openTagPtr;

        // Skip whitespace after open tag
        while (isset($tokens[$ptr + 1]) && $tokens[$ptr + 1]['code'] === T_WHITESPACE) {
            $ptr++;
        }

        // Skip comment block if present
        if (isset($tokens[$ptr + 1]) && $tokens[$ptr + 1]['code'] === T_COMMENT) {
            while (isset($tokens[$ptr + 1]) && $tokens[$ptr + 1]['code'] === T_COMMENT) {
                $ptr++;
            }
        } elseif (isset($tokens[$ptr + 1]) && $tokens[$ptr + 1]['code'] === T_DOC_COMMENT_OPEN_TAG) {
            $ptr++;
            while (isset($tokens[$ptr + 1]) && $tokens[$ptr]['code'] !== T_DOC_COMMENT_CLOSE_TAG) {
                $ptr++;
            }
        }

        return $ptr;
    }

    private function findLastUseSemicolon(File $phpcsFile): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $lastUseSemicolon = null;

        foreach ($tokens as $ptr => $token) {
            if ($token['code'] !== T_USE) {
                continue;
            }

            $prevPtr = $phpcsFile->findPrevious(T_WHITESPACE, $ptr - 1, null, true);
            if ($prevPtr !== false && $tokens[$prevPtr]['code'] === T_CLOSE_PARENTHESIS) {
                continue;
            }

            $lookPtr = $ptr + 1;
            while (isset($tokens[$lookPtr]) && $tokens[$lookPtr]['code'] !== T_SEMICOLON) {
                $lookPtr++;
            }

            if (isset($tokens[$lookPtr])) {
                $lastUseSemicolon = $lookPtr;
            }
        }

        return $lastUseSemicolon;
    }
}
