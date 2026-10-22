<?php namespace Tests;
/**
 * Copyright 2026 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;

/**
 * Covers the SummitOrder indexes behind getAllByOwnerEmailAndOwnerNotSet(), getByHashLockExclusive()
 * and getByPaymentGatewayCartIdExclusiveLock(). The index columns are checked in
 * INFORMATION_SCHEMA and the predicates are explained to check the optimizer can pick them (possible_keys).
 *
 * Class DoctrineSummitOrderRepositoryIndexesTest
 */
final class DoctrineSummitOrderRepositoryIndexesTest extends TestCase
{
    private function connection()
    {
        return Registry::getManager(SilverstripeBaseModel::EntityManager)->getConnection();
    }

    private function indexColumns(string $index): array
    {
        $rows = $this->connection()->fetchAllAssociative(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'SummitOrder' AND INDEX_NAME = ?
             ORDER BY SEQ_IN_INDEX",
            [$index]
        );
        return array_column($rows, 'COLUMN_NAME');
    }

    private function explainPossibleKeys(string $sql, array $params): string
    {
        $rows = $this->connection()->fetchAllAssociative("EXPLAIN " . $sql, $params);
        return (string)($rows[0]['possible_keys'] ?? '');
    }

    public function testOwnerEmailOwnerIndexExists(): void
    {
        $this->assertSame(['OwnerEmail', 'OwnerID'], $this->indexColumns('IDX_SummitOrder_OwnerEmail_Owner'));
    }

    public function testHashIndexExists(): void
    {
        $this->assertSame(['Hash'], $this->indexColumns('IDX_SummitOrder_Hash'));
    }

    public function testPaymentGatewayCartIdIndexExists(): void
    {
        $this->assertSame(['PaymentGatewayCartId'], $this->indexColumns('IDX_SummitOrder_PaymentGatewayCartId'));
    }

    public function testAssociationLookupCanUseOwnerEmailOwnerIndex(): void
    {
        $keys = $this->explainPossibleKeys(
            "SELECT ID FROM SummitOrder WHERE OwnerEmail = ? AND OwnerID IS NULL", ['a@b.com']);
        $this->assertStringContainsString('IDX_SummitOrder_OwnerEmail_Owner', $keys);
    }

    public function testHashLookupCanUseHashIndex(): void
    {
        $keys = $this->explainPossibleKeys("SELECT ID FROM SummitOrder WHERE Hash = ?", ['x']);
        $this->assertStringContainsString('IDX_SummitOrder_Hash', $keys);
    }

    public function testCartIdLookupCanUseCartIdIndex(): void
    {
        $keys = $this->explainPossibleKeys("SELECT ID FROM SummitOrder WHERE PaymentGatewayCartId = ?", ['x']);
        $this->assertStringContainsString('IDX_SummitOrder_PaymentGatewayCartId', $keys);
    }
}
