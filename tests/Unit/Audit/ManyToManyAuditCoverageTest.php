<?php namespace Tests\Unit\Audit;
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

use App\Audit\AbstractAuditLogFormatter;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use ReflectionClass;
use Tests\TestCase;

/**
 * AuditLogFormatterFactory hands EVENT_COLLECTION_MANYTOMANY_UPDATE / DELETE to the formatter
 * configured for the owner entity in config/audit_log.php and only falls back to the
 * Default* formatters when the entity has no configuration (ADR-004). A configured formatter
 * without a case for those events returns null and the entry is dropped, so every configured
 * entity that owns a many-to-many collection must have all its formatters handle them.
 *
 * Handling is detected the way AuditLogFormatterUpdateGatewayTest does it: a source scan of the
 * formatter class, its parents below AbstractAuditLogFormatter and the traits they use.
 */
class ManyToManyAuditCoverageTest extends TestCase
{
    private const REQUIRED_EVENTS = [
        'EVENT_COLLECTION_MANYTOMANY_UPDATE',
        'EVENT_COLLECTION_MANYTOMANY_DELETE',
    ];

    public function testEveryConfiguredFormatterHandlesManyToManyEventsOfItsEntity(): void
    {
        $em = app(ManagerRegistry::class)->getManager();
        $offenders = [];

        foreach (config('audit_log.entities') as $entity => $config) {
            if (($config['enabled'] ?? true) === false) {
                continue;
            }

            $fields = $this->getOwningManyToManyFields($em->getClassMetadata($entity));
            if (empty($fields)) {
                continue;
            }

            foreach ($this->getConfiguredFormatters($config) as $formatter) {
                $missing = array_diff(self::REQUIRED_EVENTS, $this->getHandledEvents($formatter));
                if (!empty($missing)) {
                    $offenders[] = sprintf(
                        "%s (%s) is formatted by %s, which does not handle %s",
                        $entity,
                        implode(', ', $fields),
                        $formatter,
                        implode(' / ', $missing)
                    );
                }
            }
        }

        $this->assertEmpty(
            $offenders,
            "Configured entities that own many-to-many collections must have their formatter handle " .
            "EVENT_COLLECTION_MANYTOMANY_UPDATE / DELETE, otherwise those changes are dropped from the audit log " .
            "(see adr/004-m2m-audit-logging-join-table-query.md, 'Adding M2M audit to a new entity'). Offenders:\n" .
            implode("\n", $offenders)
        );
    }

    /**
     * @return string[] owning-side many-to-many association field names
     */
    private function getOwningManyToManyFields(ClassMetadata $metadata): array
    {
        $fields = [];
        foreach ($metadata->associationMappings as $field => $mapping) {
            $type = is_array($mapping) ? $mapping['type'] : $mapping->type();
            $owning = is_array($mapping) ? $mapping['isOwningSide'] : $mapping->isOwningSide();
            if ($type === ClassMetadata::MANY_TO_MANY && $owning) {
                $fields[] = $field;
            }
        }
        return $fields;
    }

    /**
     * @return string[] the default strategy plus every route-scoped strategies[] formatter
     */
    private function getConfiguredFormatters(array $config): array
    {
        $formatters = [];
        if (!empty($config['strategy'])) {
            $formatters[] = $config['strategy'];
        }
        foreach ($config['strategies'] ?? [] as $strategy) {
            if (!empty($strategy['formatter'])) {
                $formatters[] = $strategy['formatter'];
            }
        }
        return array_values(array_unique($formatters));
    }

    /**
     * @return string[] required event constants found in the formatter class, its parents
     *                  below AbstractAuditLogFormatter and the traits they use
     */
    private function getHandledEvents(string $formatter): array
    {
        $source = '';
        $class = new ReflectionClass($formatter);
        while ($class && $class->getName() !== AbstractAuditLogFormatter::class) {
            $source .= $this->getSourceWithTraits($class);
            $class = $class->getParentClass() ?: null;
        }

        return array_values(array_filter(
            self::REQUIRED_EVENTS,
            fn(string $event) => str_contains($source, $event)
        ));
    }

    private function getSourceWithTraits(ReflectionClass $class): string
    {
        $source = file_get_contents($class->getFileName());
        foreach ($class->getTraits() as $trait) {
            $source .= $this->getSourceWithTraits($trait);
        }
        return $source;
    }
}
