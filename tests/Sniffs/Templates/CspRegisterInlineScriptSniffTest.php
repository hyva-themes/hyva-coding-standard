<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2022-present. All rights reserved.
 * This product is licensed under the BSD-3-Clause license.
 * See LICENSE.txt for details.
 */

declare(strict_types=1);

namespace HyvaThemes\Sniffs\Templates;

use HyvaThemes\CodingStandard\SniffTestAbstract;
use PHP_CodeSniffer\Config as CodeSnifferConfig;
use PHP_CodeSniffer\Files\DummyFile;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Ruleset as CodeSnifferRuleset;

/**
 * @covers \HyvaThemes\Sniffs\Templates\CspRegisterInlineScriptSniff
 */
class CspRegisterInlineScriptSniffTest extends SniffTestAbstract
{
    protected function getFileUnderTest(): string
    {
        return 'src/HyvaThemes/Sniffs/Templates/CspRegisterInlineScriptSniff.php';
    }

    private function processCodeForArea(string $code, string $area): File
    {
        $paths = [
            'base' => '/app/code/Vendor/Module/view/base/templates/test.phtml',
            'adminhtml' => '/app/code/Vendor/Module/view/adminhtml/templates/test.phtml',
            'frontend' => '/app/code/Vendor/Module/view/frontend/templates/test.phtml',
        ];
        $path = $paths[$area] ?? $paths['frontend'];

        // DummyFile reads "phpcs_input_file: <path>" from the first line
        $codeWithPath = "phpcs_input_file: $path\n$code";

        $config = new CodeSnifferConfig([], false);
        $ruleSet = new CodeSnifferRuleset($config);
        $ruleSet->registerSniffs([$this->getFileUnderTest()], [], []);
        $ruleSet->populateTokenListeners();

        $file = new DummyFile($codeWithPath, $ruleSet, $config);
        $file->process();
        return $file;
    }

    // --- Frontend area tests ---

    public function testFrontendPassesWithCspCallAfterScript(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendPassesWithCspCallAfterScriptWithoutSemicolon(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript() ?>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendPassesWithMultipleScriptBlocks(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
<div>content</div>
<script>var y = 2;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendFailsWithoutCspCall(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<div>content</div>
EOF
            , 'frontend');

        $this->assertGreaterThan(0, $file->getWarningCount());
        $this->assertStringContainsString(
            CspRegisterInlineScriptSniff::MSG_MISSING_CSP_CALL,
            $this->getFirstMessage($file->getWarnings())
        );
    }

    public function testFrontendFailsWithHtmlBeforeCspCall(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<div>content</div>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'frontend');

        $this->assertGreaterThan(0, $file->getWarningCount());
    }

    public function testFrontendFailsWithDifferentPhpCodeAfterScript(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php echo "hello"; ?>
EOF
            , 'frontend');

        $this->assertGreaterThan(0, $file->getWarningCount());
    }

    public function testFrontendFailsWithoutUseImport(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'frontend');

        $warnings = $file->getWarnings();
        $allMessages = $this->collectAllWarningMessages($warnings);
        $this->assertContains(CspRegisterInlineScriptSniff::MSG_MISSING_USE_IMPORT, $allMessages);
    }

    public function testFrontendFailsWithoutVarAnnotation(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'frontend');

        $warnings = $file->getWarnings();
        $allMessages = $this->collectAllWarningMessages($warnings);
        $this->assertContains(CspRegisterInlineScriptSniff::MSG_MISSING_VAR_ANNOTATION, $allMessages);
    }

    // --- Base area tests ---

    public function testBasePassesWithIssetGuardedCspCall(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php if (isset($hyvaCsp)) $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'base');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testBasePassesWithIssetGuardedCspCallWithoutSemicolon(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php if (isset($hyvaCsp)) $hyvaCsp->registerInlineScript() ?>
EOF
            , 'base');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testBaseFailsWithUnguardedCspCall(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'base');

        $this->assertGreaterThan(0, $file->getWarningCount());
        $warnings = $file->getWarnings();
        $allMessages = $this->collectAllWarningMessages($warnings);
        $this->assertContains(CspRegisterInlineScriptSniff::MSG_MISSING_CSP_CALL_WITH_ISSET, $allMessages);
    }

    // --- Adminhtml area tests ---

    public function testAdminhtmlPassesWithoutCspCall(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php /* adminhtml template */ ?>
<script>var x = 1;</script>
EOF
            , 'adminhtml');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testAdminhtmlFailsWithCspCallPresent(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'adminhtml');

        $this->assertGreaterThan(0, $file->getWarningCount());
        $warnings = $file->getWarnings();
        $allMessages = $this->collectAllWarningMessages($warnings);
        $this->assertContains(CspRegisterInlineScriptSniff::MSG_UNEXPECTED_CSP_CALL, $allMessages);
    }

    // --- Fixer tests ---

    private function fixCodeForArea(string $code, string $area): string
    {
        $file = $this->processCodeForArea($code, $area);
        $file->fixer->fixFile();
        // Strip the "phpcs_input_file: ..." prefix line added by processCodeForArea
        $contents = $file->fixer->getContents();
        return preg_replace('/^phpcs_input_file:[^\n]*\n/', '', $contents);
    }

    public function testFixerInsertsCspCallAfterScriptOnFrontend(): void
    {
        $fixed = $this->fixCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<div>content</div>
EOF
            , 'frontend');

        $this->assertStringContainsString(
            "</script>\n<?php \$hyvaCsp->registerInlineScript(); ?>\n<div>content</div>",
            $fixed
        );
    }

    public function testFixerInsertsCspCallAfterScriptAtEndOfFile(): void
    {
        $fixed = $this->fixCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
EOF
            , 'frontend');

        $this->assertStringContainsString(
            "</script>\n<?php \$hyvaCsp->registerInlineScript(); ?>",
            $fixed
        );
    }

    public function testFixerInsertsCspCallWithIssetForBaseArea(): void
    {
        $fixed = $this->fixCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<div>content</div>
EOF
            , 'base');

        $this->assertStringContainsString(
            "</script>\n<?php if (isset(\$hyvaCsp)) \$hyvaCsp->registerInlineScript(); ?>\n<div>content</div>",
            $fixed
        );
    }

    public function testFixerInsertsMissingUseImportAfterExistingUse(): void
    {
        $fixed = $this->fixCodeForArea(<<<'EOF'
<?php
use Magento\Framework\Escaper;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'frontend');

        $this->assertStringContainsString(
            "use Magento\\Framework\\Escaper;\nuse Hyva\\Theme\\ViewModel\\HyvaCsp;",
            $fixed
        );
    }

    public function testFixerInsertsMissingVarAnnotationAfterExistingAnnotation(): void
    {
        $fixed = $this->fixCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var Escaper $escaper */
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'frontend');

        $this->assertStringContainsString(
            "/** @var Escaper \$escaper */\n/** @var HyvaCsp \$hyvaCsp */",
            $fixed
        );
    }

    public function testFixerInsertsMissingVarAnnotationAfterUseWhenNoAnnotationsExist(): void
    {
        $fixed = $this->fixCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'frontend');

        $this->assertStringContainsString(
            "use Hyva\\Theme\\ViewModel\\HyvaCsp;\n\n/** @var HyvaCsp \$hyvaCsp */",
            $fixed
        );
    }

    public function testFixerInsertsBothUseAndAnnotationWhenBothMissing(): void
    {
        $fixed = $this->fixCodeForArea(<<<'EOF'
<?php
use Magento\Framework\Escaper;
/** @var Escaper $escaper */
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'frontend');

        $this->assertStringContainsString('use Hyva\\Theme\\ViewModel\\HyvaCsp;', $fixed);
        $this->assertStringContainsString('/** @var HyvaCsp $hyvaCsp */', $fixed);
    }

    /**
     * Collect all warning messages from the warnings array.
     *
     * @param array $warnings
     * @return string[]
     */
    private function collectAllWarningMessages(array $warnings): array
    {
        $messages = [];
        foreach ($warnings as $line => $columns) {
            foreach ($columns as $column => $warningList) {
                foreach ($warningList as $warning) {
                    $messages[] = $warning['message'];
                }
            }
        }
        return $messages;
    }
}
