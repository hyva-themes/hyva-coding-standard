<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2022-present. All rights reserved.
 * This product is licensed under the BSD-3-Clause license.
 * See LICENSE.txt for details.
 */

declare(strict_types=1);

namespace HyvaThemes\Sniffs\Annotation;

use HyvaThemes\CodingStandard\SniffTestAbstract;

/**
 * @covers \HyvaThemes\Sniffs\Annotation\InheritsMethodAnnotationSniff
 */
class InheritsMethodAnnotationSniffTest extends SniffTestAbstract
{
    protected function getFileUnderTest(): string
    {
        return 'src/HyvaThemes/Sniffs/Annotation/InheritsMethodAnnotationSniff.php';
    }

    public function testIgnoresMethodsWithoutAnnotation(): void
    {
        $file = $this->processCode(<<<EOF
<?php

class FooBar {
    public function baz(): void
    {

    }
}
EOF
        );

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testIgnoresMethodsWithEmptyAnnotation()
    {
        $file = $this->processCode(<<<EOF
<?php

class FooBar {

    /**
     *
     */
    public function baz(): void
    {

    }
}
EOF
        );

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testIgnoresMethodsWithRegularAnnotation()
    {
        $file = $this->processCode(<<<EOF
<?php

class FooBar {

    /**
     * This method does nothing
     */
    public function baz(): void
    {

    }
}
EOF
        );

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testWarnsAboutMethodsWithInheritDocAnnotation()
    {
        $file = $this->processCode(<<<EOF
<?php

class FooBar {

    /**
     * @inheritDoc
     */
    public function baz(): void
    {

    }
}
EOF
        );

        $this->assertSame(1, $file->getWarningCount());
    }

    public function testWarnsAboutMethodsWithInheritDocLowercaseAnnotation()
    {
        $file = $this->processCode(<<<EOF
<?php

class FooBar {

    /**
     * @inheritdoc
     */
    public function baz(): void
    {

    }
}
EOF
        );

        $this->assertSame(1, $file->getWarningCount());
    }

    public function testIgnoresInheritDocOnClass()
    {
        $file = $this->processCode(<<<EOF
<?php

/**
 * @inheritdoc
 */
class FooBar {
    public function baz(): void
    {

    }
}
EOF
        );

        $this->assertSame(0, $file->getWarningCount());
    }

    public function testWarnsAboutInheritDocOnMethodAndIgnoresInheritDocClass()
    {
        $file = $this->processCode(<<<EOF
<?php

/**
 * @inheritdoc
 */
class FooBar {
    /**
     * @inheritdoc
     */
    public function baz(): void
    {

    }
}
EOF
        );

        $this->assertSame(1, $file->getWarningCount());
    }

    public function testWarnsAboutInheritDocWithBraces()
    {
        $file = $this->processCode(<<<EOF
<?php

class FooBar {

    /**
     * {@inheritDoc}
     */
    public function baz(): void
    {

    }
}
EOF
        );

        $this->assertSame(1, $file->getWarningCount());
    }
}
