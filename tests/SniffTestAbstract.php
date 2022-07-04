<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2022-present. All rights reserved.
 * This product is licensed under the BSD-3-Clause license.
 * See LICENSE.txt for details.
 */

declare(strict_types=1);

namespace HyvaThemes\CodingStandard;

use PHP_CodeSniffer\Files\DummyFile;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Ruleset as CodeSnifferRuleset;
use PHP_CodeSniffer\Config as CodeSnifferConfig;
use PHP_CodeSniffer\Util\Tokens as CodeSnifferTokens;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.Superglobals)
 */
abstract class SniffTestAbstract extends TestCase
{
    private CodeSnifferConfig $phpcsConfig;

    /**
     * @before
     */
    public function prepareCodeSniffer(): void
    {
        if (!defined('PHP_CODESNIFFER_IN_TESTS')) {
            define('PHP_CODESNIFFER_IN_TESTS', true);
        }
        if (!defined('PHP_CODESNIFFER_VERBOSITY')) {
            define('PHP_CODESNIFFER_VERBOSITY', 0);
        }
        if (!defined('PHP_CODESNIFFER_CBF')) {
            define('PHP_CODESNIFFER_CBF', false);
        }

        // Load tokens via autoloader
        new CodeSnifferTokens;

        $this->phpcsConfig = new CodeSnifferConfig([], false);
    }

    abstract protected function getFileUnderTest(): string;

    /**
     * phpcs:disable Magento2.PHP.FinalImplementation.FoundFinal
     */
    final protected function processInlinePhpCode(string $code): File
    {
        if ('<?php ' !== substr($code, 0, 6)) {
            $code = '<?php ' . $code;
        }
        return $this->processCode($code);
    }

    /**
     * phpcs:disable Magento2.PHP.FinalImplementation.FoundFinal
     */
    final protected function processCode(string $code): File
    {
        $ruleSet = new CodeSnifferRuleset($this->phpcsConfig);

        $ruleSet->registerSniffs([$this->getFileUnderTest()], [], []);
        $ruleSet->populateTokenListeners();

        $file = new DummyFile($code, $ruleSet, $this->phpcsConfig);

        $file->process();

        return $file;
    }

    final protected function getFirstMessage(array $messages): string
    {
        $messages = array_shift($messages);
        if (!is_array($messages)) {
            return '';
        }

        $messages = array_shift($messages);
        if (!is_array($messages)) {
            return '';
        }

        $message = array_shift($messages);
        if (!is_array($message)) {
            return '';
        }

        return $message['message'] ?? '';
    }
}
