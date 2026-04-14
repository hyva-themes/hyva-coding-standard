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
            return self::DEFAULT_VERSION . '-';
        }

        $lowest = self::parseLowestVersion($constraint) ?? self::DEFAULT_VERSION;
        $highest = self::parseHighestVersion($constraint);

        return $highest !== null ? $lowest . '-' . $highest : $lowest . '-';
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

    public static function parseHighestVersion(string $constraint): ?string
    {
        $branches = preg_split('/\s*\|\|?\s*/', $constraint);
        if ($branches === false) {
            return null;
        }

        $highestVersion = null;

        foreach ($branches as $branch) {
            $branch = trim($branch);
            if ($branch === '' || $branch === '*') {
                continue;
            }

            $version = self::extractHighestFromBranch($branch);
            if ($version === null) {
                // If any branch has no upper bound, overall is unbounded
                return null;
            }
            if ($highestVersion === null || version_compare($version, $highestVersion, '>')) {
                $highestVersion = $version;
            }
        }

        return $highestVersion !== null ? self::normalizeMajorMinor($highestVersion) : null;
    }

    private static function extractHighestFromBranch(string $branch): ?string
    {
        $parts = preg_split('/[,\s]+/', $branch);
        if ($parts === false) {
            return null;
        }

        $upperBound = null;
        $upperIsInclusive = false;

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || $part === '*') {
                continue;
            }

            $bound = null;
            $inclusive = false;

            // Explicit upper bounds: < and <=
            if (preg_match('/^<=?\s*(\d+(?:\.\d+(?:\.\d+)?)?)/', $part, $matches)) {
                $bound = $matches[1];
                $inclusive = str_starts_with($part, '<=');
            }
            // Caret operator: ^X.Y → upper bound <(X+1).0
            elseif (preg_match('/^\^\s*(\d+)/', $part, $matches)) {
                $major = (int)$matches[1];
                $bound = ($major + 1) . '.0';
                $inclusive = false;
            }
            // Tilde operator: ~X.Y → upper bound <X.(Y+1)
            elseif (preg_match('/^~\s*(\d+)(?:\.(\d+))?/', $part, $matches)) {
                $major = (int)$matches[1];
                if (isset($matches[2])) {
                    $minor = (int)$matches[2];
                    $bound = $major . '.' . ($minor + 1);
                } else {
                    // ~X means <(X+1).0
                    $bound = ($major + 1) . '.0';
                }
                $inclusive = false;
            }

            if ($bound !== null) {
                if ($upperBound === null || version_compare($bound, $upperBound, '<')
                    || (version_compare($bound, $upperBound, '==') && !$inclusive)) {
                    $upperBound = $bound;
                    $upperIsInclusive = $inclusive;
                }
            }
        }

        if ($upperBound === null) {
            return null;
        }

        if ($upperIsInclusive) {
            return $upperBound;
        }

        // Exclusive upper bound: convert to highest matching major.minor
        $boundParts = explode('.', $upperBound);
        $major = (int)$boundParts[0];
        $minor = (int)($boundParts[1] ?? '0');

        if ($minor > 0) {
            return $major . '.' . ($minor - 1);
        }

        // <X.0 → highest is (X-1).99
        return ($major - 1) . '.99';
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
                if (preg_match('/^<=?/', $part)) {
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
