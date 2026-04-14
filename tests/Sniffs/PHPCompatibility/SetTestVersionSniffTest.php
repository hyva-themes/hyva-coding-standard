<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HyvaThemes\CodingStandard\Sniffs\PHPCompatibility;

use HyvaThemes\CodingStandard\SniffTestAbstract;
use HyvaThemes\Sniffs\PHPCompatibility\SetTestVersionSniff;
use PHP_CodeSniffer\Config;

class SetTestVersionSniffTest extends SniffTestAbstract
{
    protected function getFileUnderTest(): string
    {
        return dirname(__DIR__, 3) . '/src/HyvaThemes/Sniffs/PHPCompatibility/SetTestVersionSniff.php';
    }

    /**
     * @before
     */
    public function resetSniffState(): void
    {
        SetTestVersionSniff::reset();
    }

    public function testSniffSetsTestVersionFromComposerJson(): void
    {
        // The sniff uses the file's directory, so we test that it runs without error
        // and sets a testVersion config value
        Config::setConfigData('testVersion', null, true);

        $this->processInlinePhpCode('echo "hello";');

        $testVersion = Config::getConfigData('testVersion');
        $this->assertNotNull($testVersion, 'testVersion should be set after sniff processes');
        $this->assertMatchesRegularExpression('/^\d+\.\d+-(\d+\.\d+)?$/', $testVersion, 'testVersion should be in Major.Minor-Major.Minor or Major.Minor- format');
    }

    public function testSniffPreservesExplicitTestVersion(): void
    {
        Config::setConfigData('testVersion', '7.4-', true);

        $this->processInlinePhpCode('echo "hello";');

        $this->assertSame('7.4-', Config::getConfigData('testVersion'));
    }

    public function testSniffProducesNoWarningsOrErrors(): void
    {
        $file = $this->processInlinePhpCode('echo "hello";');

        $this->assertSame(0, $file->getErrorCount());
        $this->assertSame(0, $file->getWarningCount());
    }
}
