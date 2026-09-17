<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Integration\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * QCC-NFR-004, QCC-DATA-007/008: proves that the customer dashboard (US2), Admin
 * adjustment (US3), and REST API (US1) paths all share one non-duplicated
 * balance/ledger access rule set, rather than three independently reimplemented ones.
 *
 * Statically scans every `Controller/*` and `Block/*` source file (used by the
 * customer-facing and Admin-facing paths) and asserts none of them reference
 * `Model\ResourceModel\*` or the raw `Model\CreditBalance`/`Model\CreditTransaction`
 * entity models directly - they may only reach balance/ledger state through
 * `Api\CreditBalanceManagementInterface`, `Api\CreditTransactionManagementInterface`,
 * and `Api\CreditLedgerInterface`.
 *
 * The REST API path (`etc/webapi.xml`) is not scanned separately: it routes directly
 * to the `Api\*ManagementInterface` methods, whose only concrete implementations are
 * `Model\Service\CreditBalanceManagement` and `Model\Service\CreditTransactionManagement`
 * - i.e. the service-contract boundary itself, which is the one place persistence
 * access through `Model\ResourceModel\*` is expected and correct.
 */
class ServiceContractBoundaryTest extends TestCase
{
    /**
     * Forbidden fully-qualified class-name fragments, matched with a negative
     * lookahead so that legitimate longer names sharing the same prefix
     * (e.g. `CreditBalanceManagementInterface`, `CreditTransactionInterface`,
     * `CreditTransactionResult`) are never mistakenly flagged.
     */
    private const FORBIDDEN_PATTERNS = [
        '/Model\\\\ResourceModel\\\\/',
        '/Model\\\\CreditBalance(?![A-Za-z0-9_])/',
        '/Model\\\\CreditTransaction(?![A-Za-z0-9_])/',
    ];

    private const SCANNED_DIRECTORIES = ['Controller', 'Block'];

    public function testControllerAndBlockClassesNeverBypassServiceContracts(): void
    {
        $moduleRoot = dirname(__DIR__, 3);
        $violations = [];

        foreach (self::SCANNED_DIRECTORIES as $directory) {
            $path = $moduleRoot . '/' . $directory;
            if (!is_dir($path)) {
                continue;
            }

            foreach ($this->findPhpFiles($path) as $file) {
                $content = file_get_contents($file);
                foreach (self::FORBIDDEN_PATTERNS as $pattern) {
                    if (preg_match($pattern, $content)) {
                        $violations[] = str_replace($moduleRoot . '/', '', $file);
                    }
                }
            }
        }

        self::assertSame(
            [],
            array_values(array_unique($violations)),
            "The following files bypass the service-contract boundary by referencing "
            . "Model\\ResourceModel\\* or the raw CreditBalance/CreditTransaction entity "
            . "models directly, instead of going exclusively through "
            . "Api\\CreditBalanceManagementInterface, Api\\CreditTransactionManagementInterface, "
            . "or Api\\CreditLedgerInterface: " . implode(', ', array_unique($violations))
        );
    }

    /**
     * @param string $directory
     * @return string[]
     */
    private function findPhpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                $files[] = $fileInfo->getPathname();
            }
        }

        return $files;
    }
}
