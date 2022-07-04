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

/**
 * @covers \HyvaThemes\Sniffs\Templates\BlockEscapeInTemplateSniff
 */
class BlockEscapeInTemplateSniffTest extends SniffTestAbstract
{

    protected function getFileUnderTest(): string
    {
        return 'src/HyvaThemes/Sniffs/Templates/BlockEscapeInTemplateSniff.php';
    }

    public function testAllowsBlockMethodsThatDoNotEscape(): void
    {
        $file = $this->processCode(<<<EOF
<div><?= \$block->getItemHtml() ?></div>
EOF
        );
        $this->assertSame('', $this->getFirstMessage($file->getWarnings()));
    }

    public static function escapeMethodProvider(): array
    {
        return [
            ['escapeHtml'],
            ['escapeHtmlAttr'],
            ['escapeUrl'],
            ['escapeJs'],
            ['escapeCss'],
            ['escapeJsQuote'],
            ['escapeXssInUrl'],
            ['escapeQuote'],

        ];
    }

    /**
     * @dataProvider escapeMethodProvider
     */
    public function testAllowsEscaperEscapeMethods(string $escapeMethod): void
    {
        $file = $this->processCode(<<<EOF
<div>
    <?= \$escaper->{$escapeMethod}(__('Foo Bar Baz)) ?>
 </div>
EOF
        );

        $this->assertSame(0, $file->getWarningCount());
    }

    /**
     * @dataProvider escapeMethodProvider
     */
    public function testWarnsBlockEscapeWithoutWhitespaces(string $escapeMethod): void
    {
        $file = $this->processCode(<<<EOF
<div>
    <?= \$block->{$escapeMethod}(__('Foo Bar Baz)) ?>
 </div>
EOF
        );

        $expected = sprintf(BlockEscapeInTemplateSniff::ESCAPE_METHOD_ON_BLOCK_MSG, $escapeMethod);
        $this->assertSame($expected, $this->getFirstMessage($file->getWarnings()));
    }

    /**
     * @dataProvider escapeMethodProvider
     */
    public function testWarnsBlockEscapeWithWhitespace(string $escapeMethod): void
    {
        $file = $this->processCode(<<<EOF
<div>
    <?= \$block
    -> {$escapeMethod}
    (__('Foo Bar Baz)) ?>
 </div>
EOF
        );

        $expected = sprintf(BlockEscapeInTemplateSniff::ESCAPE_METHOD_ON_BLOCK_MSG, $escapeMethod);
        $this->assertSame($expected, $this->getFirstMessage($file->getWarnings()));
    }
}
