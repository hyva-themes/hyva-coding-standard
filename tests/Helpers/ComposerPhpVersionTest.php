<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HyvaThemes\CodingStandard\Helpers;

use HyvaThemes\Helpers\ComposerPhpVersion;
use PHPUnit\Framework\TestCase;

class ComposerPhpVersionTest extends TestCase
{
    private function fixturePath(string $name): string
    {
        return __DIR__ . '/fixtures/' . $name;
    }

    public function testDetectWithPhp80Constraint(): void
    {
        // >=8.0 has no upper bound
        $this->assertSame('8.0-', ComposerPhpVersion::detect($this->fixturePath('with-php-80')));
    }

    public function testDetectWithPhp81Constraint(): void
    {
        // ^8.1 → 8.1 through 8.99
        $this->assertSame('8.1-8.99', ComposerPhpVersion::detect($this->fixturePath('with-php-81')));
    }

    public function testDetectWithOrConstraintReturnsRange(): void
    {
        // ^7.4 || ^8.0 → 7.4 through 8.99
        $this->assertSame('7.4-8.99', ComposerPhpVersion::detect($this->fixturePath('with-php-74-or-80')));
    }

    public function testDetectWithMajorOnlyVersion(): void
    {
        // ^8 → 8.0 through 8.99
        $this->assertSame('8.0-8.99', ComposerPhpVersion::detect($this->fixturePath('with-major-only')));
    }

    public function testDetectWithoutPhpConstraintReturnsDefault(): void
    {
        $this->assertSame('8.0-', ComposerPhpVersion::detect($this->fixturePath('no-php-constraint')));
    }

    public function testDetectWithoutComposerJsonReturnsDefault(): void
    {
        $this->assertSame('8.0-', ComposerPhpVersion::detect($this->fixturePath('no-composer')));
    }

    /**
     * @dataProvider constraintParsingProvider
     */
    public function testParseLowestVersion(string $constraint, string $expected): void
    {
        $this->assertSame($expected, ComposerPhpVersion::parseLowestVersion($constraint));
    }

    public function constraintParsingProvider(): array
    {
        return [
            'greater-or-equal'          => ['>=8.1', '8.1'],
            'caret major.minor'         => ['^8.1', '8.1'],
            'caret major only'          => ['^8', '8.0'],
            'tilde major.minor.patch'   => ['~8.1.0', '8.1'],
            'tilde major.minor'         => ['~8.1', '8.1'],
            'tilde major only'          => ['~8', '8.0'],
            'greater-or-equal major'    => ['>=8', '8.0'],
            'or constraint'             => ['^7.4 || ^8.0', '7.4'],
            'or with pipe'              => ['^7.4|^8.0', '7.4'],
            'and with space'            => ['>=8.0 <8.4', '8.0'],
            'and with comma'            => ['>=8.0,<8.4', '8.0'],
            'patch version stripped'    => ['>=8.1.5', '8.1'],
            'exact version'             => ['8.1.3', '8.1'],
            'exact major.minor'         => ['8.2', '8.2'],
            'complex range'             => ['>=7.4 <8.0 || >=8.1', '7.4'],
        ];
    }

    public function testParseLowestVersionReturnsNullForWildcard(): void
    {
        $this->assertNull(ComposerPhpVersion::parseLowestVersion('*'));
    }

    /**
     * @dataProvider highestVersionProvider
     */
    public function testParseHighestVersion(string $constraint, ?string $expected): void
    {
        $this->assertSame($expected, ComposerPhpVersion::parseHighestVersion($constraint));
    }

    public function highestVersionProvider(): array
    {
        return [
            'greater-or-equal unbounded'   => ['>=8.1', null],
            'caret major.minor'            => ['^8.1', '8.99'],
            'caret major only'             => ['^8', '8.99'],
            'tilde major.minor'            => ['~8.1', '8.1'],
            'tilde major.minor.patch'      => ['~8.1.0', '8.1'],
            'tilde major only'             => ['~8', '8.99'],
            'explicit less-than'           => ['>=8.0 <8.4', '8.3'],
            'explicit less-than-or-equal'  => ['>=8.0 <=8.3', '8.3'],
            'less-than with comma'         => ['>=8.0,<8.4', '8.3'],
            'less-than at major boundary'  => ['>=7.4 <8.0', '7.99'],
            'caret with explicit upper'    => ['^8.0 <8.4', '8.3'],
            'or branches take max'         => ['^7.4 || ^8.0', '8.99'],
            'or with explicit uppers'      => ['>=7.4 <8.0 || >=8.1 <8.4', '8.3'],
            'exact version'                => ['8.1.3', null],
            'wildcard'                     => ['*', null],
        ];
    }

    public function testParseHighestVersionReturnsNullForWildcard(): void
    {
        $this->assertNull(ComposerPhpVersion::parseHighestVersion('*'));
    }
}
