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

        return $this->processCodeForPath($code, $path);
    }

    private function processCodeForPath(string $code, string $path): File
    {
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

    public function testBasePassesWithIssetGuardedCspCallAndAdditionalCode(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php if (isset($hyvaCsp)) $hyvaCsp->registerInlineScript(); ?>
<?php echo "more code"; ?>
EOF
            , 'base');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testBasePassesWithCspCallInMultiLinePhpBlock(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php
    if (isset($hyvaCsp)) $hyvaCsp->registerInlineScript();
endif;
EOF
            , 'base');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testBasePassesWithCspCallInBlockWithoutCloseTag(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php
    if (isset($hyvaCsp)) $hyvaCsp->registerInlineScript();
    $someOtherCode = true;
EOF
            , 'base');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendPassesWithCspCallAndAdditionalCode(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); $otherCode = true; ?>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendPassesWithCspCallInMultiLinePhpBlock(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php
    $hyvaCsp->registerInlineScript();
    echo "more";
?>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
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

    // --- Default theme package tests ---

    public function testDefaultThemePackageSkipsCspCheck(): void
    {
        $file = $this->processCodeForPath(<<<'EOF'
<?php /** template */ ?>
<script>var x = 1;</script>
EOF
            , '/vendor/hyva-themes/magento2-default-theme/Magento_Catalog/templates/test.phtml');

        $this->assertSame(0, $file->getWarningCount());
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

    public function testFixerInsertsUseImportAfterDeclareWhenNoUseExists(): void
    {
        $fixed = $this->fixCodeForArea(<<<'EOF'
<?php
/**
 * License comment
 */

declare(strict_types=1);

/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'frontend');

        $this->assertStringContainsString(
            "declare(strict_types=1);\n\nuse Hyva\\Theme\\ViewModel\\HyvaCsp;",
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

    public function testFixerInsertsVarAnnotationInHeaderNotAfterBodyVar(): void
    {
        $fixed = $this->fixCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
use Magento\Catalog\Model\Product;
/** @var Product $product */
?>
<div>
    <?php /** @var Product $item */ ?>
    <?php foreach ($items as $item): ?>
        <span><?= $item->getName() ?></span>
    <?php endforeach; ?>
</div>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'frontend');

        // Annotation should be in the header, after the existing header @var
        $this->assertStringContainsString(
            "/** @var Product \$product */\n/** @var HyvaCsp \$hyvaCsp */",
            $fixed
        );
    }

    // --- Script type filtering tests ---

    public function testFrontendPassesWithJsonScriptWithoutCspCall(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
/** some template */
?>
<script type="text/json">{"key": "value"}</script>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendPassesWithApplicationJsonScriptWithoutCspCall(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
/** some template */
?>
<script type="application/json">{"key": "value"}</script>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendPassesWithApplicationLdJsonScriptWithoutCspCall(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
/** some template */
?>
<script type="application/ld+json">{"@context": "https://schema.org"}</script>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendRequiresCspForSpeculationrulesScript(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script type="speculationrules">{"prefetch": []}</script>
<div>content</div>
EOF
            , 'frontend');

        $this->assertGreaterThan(0, $file->getWarningCount());
    }

    public function testFrontendRequiresCspForExplicitTextJavascriptType(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script type="text/javascript">var x = 1;</script>
<div>content</div>
EOF
            , 'frontend');

        $this->assertGreaterThan(0, $file->getWarningCount());
    }

    public function testFrontendPassesWithMixedScriptTypes(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
<script type="text/json">{"key": "value"}</script>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendNoUseImportRequiredForJsonScriptsOnly(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
/** some template */
?>
<script type="application/json">{"key": "value"}</script>
<script type="text/json">{"other": "data"}</script>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendPassesWithCrossTokenJsonScript(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
/** some template */
$data = ['key' => 'value'];
?>
<script type="application/json"><?= json_encode($data) ?></script>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendPassesWithModuleScriptWithPhpInAttributes(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
/** some template */
$url = 'test.js';
?>
<script type="module"
        src="<?= $url ?>"
        defer
></script>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendRequiresCspForInlineModuleScript(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script type="module">import { foo } from './foo.js';</script>
<div>content</div>
EOF
            , 'frontend');

        $this->assertGreaterThan(0, $file->getWarningCount());
    }

    public function testFrontendPassesWithInlineModuleScriptAndCspCall(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script type="module">import { foo } from './foo.js';</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendPassesWithSrcScriptWithoutCspCall(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
/** some template */
?>
<script src="https://example.com/app.js"></script>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendPassesWithTypedSrcScriptWithoutCspCall(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
/** some template */
?>
<script type="text/javascript" src="https://example.com/app.js"></script>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendPassesWithModuleSrcScriptWithoutCspCall(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
/** some template */
?>
<script type="module" src="https://example.com/module.js"></script>
EOF
            , 'frontend');

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendRequiresCspForMixedSrcAndInlineScripts(): void
    {
        $file = $this->processCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script src="https://example.com/app.js"></script>
<script>var x = 1;</script>
<div>content</div>
EOF
            , 'frontend');

        $this->assertGreaterThan(0, $file->getWarningCount());
    }

    public function testFixerSkipsJsonScriptWhenFixingJsScript(): void
    {
        $fixed = $this->fixCodeForArea(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<div>content</div>
<script type="text/json">{"key": "value"}</script>
EOF
            , 'frontend');

        // CSP call inserted after the JS script
        $this->assertStringContainsString(
            "var x = 1;</script>\n<?php \$hyvaCsp->registerInlineScript(); ?>\n<div>",
            $fixed
        );
        // No CSP call after the JSON script
        $this->assertStringNotContainsString(
            '{"key": "value"}</script>' . "\n" . '<?php $hyvaCsp->registerInlineScript();',
            $fixed
        );
    }

    // --- Theme area detection tests ---

    public function testAdminhtmlThemePassesWithoutCspCall(): void
    {
        $path = __DIR__ . '/fixtures/csp/adminhtml-theme/Magento_Backend/templates/test.phtml';
        $file = $this->processCodeForPath(<<<'EOF'
<?php /* adminhtml theme template */ ?>
<script>var x = 1;</script>
EOF
            , $path);

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testAdminhtmlThemeFailsWithCspCallPresent(): void
    {
        $path = __DIR__ . '/fixtures/csp/adminhtml-theme/Magento_Backend/templates/test.phtml';
        $file = $this->processCodeForPath(<<<'EOF'
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , $path);

        $this->assertGreaterThan(0, $file->getWarningCount());
        $warnings = $file->getWarnings();
        $allMessages = $this->collectAllWarningMessages($warnings);
        $this->assertContains(CspRegisterInlineScriptSniff::MSG_UNEXPECTED_CSP_CALL, $allMessages);
    }

    // --- Hyva vs Luma theme detection tests ---

    public function testFrontendHyvaThemeRequiresCspCall(): void
    {
        $path = __DIR__ . '/fixtures/csp/frontend-hyva-theme/Magento_Theme/templates/test.phtml';
        $file = $this->processCodeForPath(<<<'EOF'
<?php /* hyva frontend template */ ?>
<script>var x = 1;</script>
EOF
            , $path);

        $this->assertGreaterThan(0, $file->getWarningCount());
    }

    public function testFrontendHyvaThemePassesWithCspCall(): void
    {
        $path = __DIR__ . '/fixtures/csp/frontend-hyva-theme/Magento_Theme/templates/test.phtml';
        $file = $this->processCodeForPath(<<<'EOF'
<?php
use Hyva\Theme\ViewModel\HyvaCsp;
/** @var HyvaCsp $hyvaCsp */
?>
<script>var x = 1;</script>
<?php $hyvaCsp->registerInlineScript(); ?>
EOF
            , $path);

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testFrontendLumaThemeSkipsCspCheck(): void
    {
        $path = __DIR__ . '/fixtures/csp/frontend-luma-theme/Magento_Theme/templates/test.phtml';
        $file = $this->processCodeForPath(<<<'EOF'
<?php /* luma frontend template */ ?>
<script>var x = 1;</script>
EOF
            , $path);

        $this->assertSame(0, $file->getWarningCount());
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
