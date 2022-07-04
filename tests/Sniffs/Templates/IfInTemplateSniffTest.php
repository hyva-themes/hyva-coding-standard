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
 * @covers \HyvaThemes\Sniffs\Templates\IfInTemplateSniff
 */
class IfInTemplateSniffTest extends SniffTestAbstract
{
    protected function getFileUnderTest(): string
    {
        return 'src/HyvaThemes/Sniffs/Templates/IfInTemplateSniff.php';
    }

    public function testAllowsIfBlockWithCurlyBraces(): void
    {
        $file = $this->processCode(<<<EOF
<div>
<?php if (true) { ?>
<h1>yes</h1>
<?php } else { ?>
<h1>no</h1>
<?php } ?>
</div>
EOF
        );

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testAllowsSingleStatementIfBlock(): void
    {
        $file = $this->processCode(<<<EOF
<?php if (true) echo "ok" ?>
EOF
        );

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testAllowsIfWithCompliantColonPlacement(): void
    {
        $file = $this->processCode(<<<EOF
<div>
<?php if (true): ?>
<h1>yes</h1>
<?php else: ?>
<h1>no</h1>
<?php endif ?>
</div>
EOF
        );

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testAllowsIfWithColonNewlinePlacement(): void
    {
        $file = $this->processCode(<<<EOF
<div>
<?php if (true):
?>
<h1>yes</h1>
<?php else:
?>
<h1>no</h1>
<?php endif ?>
</div>
EOF
        );

        $this->assertSame(0, $file->getWarningCount());
    }


    public function testWarnsIfNoSpaceAfterIfColon(): void
    {
        $file = $this->processCode(<<<EOF
<div>
<?php if (true):?>
<h1>yes</h1>
<?php endif ?>
</div>
EOF
        );

        $this->assertSame(1, $file->getWarningCount());
    }


    public function testWarnsIfNoSpaceAfterElseColon(): void
    {
        $file = $this->processCode(<<<EOF
<div>
<?php if (true): ?>
<h1>yes</h1>
<?php else:?>
<h1>no</h1>
<?php endif ?>
</div>
EOF
        );

        $this->assertSame(1, $file->getWarningCount());
    }
}
