<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HyvaThemes\Helpers;

class ComposerPhpVersion
{
    private const DEFAULT_VERSION = '8.0';

    public static function detect(string $dir): string
    {
        $constraint = self::findPhpConstraint($dir);
        if ($constraint === null) {
            return self::DEFAULT_VERSION;
        }

        return self::parseLowestVersion($constraint) ?? self::DEFAULT_VERSION;
    }

    private static function findPhpConstraint(string $dir): ?string
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        while ($dir !== '' && $dir !== DIRECTORY_SEPARATOR) {
            $composerFile = $dir . DIRECTORY_SEPARATOR . 'composer.json';
            if (is_file($composerFile)) {
                $content = file_get_contents($composerFile);
                if ($content === false) {
                    return null;
                }
                $data = json_decode($content, true);
                return $data['require']['php'] ?? null;
            }
            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
        }
        return null;
    }

    public static function parseLowestVersion(string $constraint): ?string
    {
        // Split on OR operators (|| or |)
        $branches = preg_split('/\s*\|\|?\s*/', $constraint);
        if ($branches === false) {
            return null;
        }

        $lowestVersion = null;

        foreach ($branches as $branch) {
            $branch = trim($branch);
            if ($branch === '' || $branch === '*') {
                continue;
            }

            $version = self::extractLowestFromBranch($branch);
            if ($version !== null && ($lowestVersion === null || version_compare($version, $lowestVersion, '<'))) {
                $lowestVersion = $version;
            }
        }

        return $lowestVersion !== null ? self::normalizeMajorMinor($lowestVersion) : null;
    }

    private static function extractLowestFromBranch(string $branch): ?string
    {
        // Split on comma or space (AND constraints)
        $parts = preg_split('/[,\s]+/', $branch);
        if ($parts === false) {
            return null;
        }

        $lowestVersion = null;

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || $part === '*') {
                continue;
            }

            // Match operators: >=, ^, ~, >, = or bare version
            if (preg_match('/^(?:>=?|[~^]|=)?\s*(\d+(?:\.\d+(?:\.\d+)?)?)/', $part, $matches)) {
                $version = $matches[1];

                // For < and <= operators, this is an upper bound, not a lower bound
                if (preg_match('/^<=?\s/', $part)) {
                    continue;
                }

                // For != operator, skip
                if (str_starts_with($part, '!=') || str_starts_with($part, '<>')) {
                    continue;
                }

                // For > (strict greater than), the version itself is excluded,
                // but we use it as an approximation since we normalize to major.minor
                if ($lowestVersion === null || version_compare($version, $lowestVersion, '<')) {
                    $lowestVersion = $version;
                }
            }
        }

        return $lowestVersion;
    }

    private static function normalizeMajorMinor(string $version): string
    {
        $parts = explode('.', $version);
        $major = $parts[0];
        $minor = $parts[1] ?? '0';
        return $major . '.' . $minor;
    }
}
