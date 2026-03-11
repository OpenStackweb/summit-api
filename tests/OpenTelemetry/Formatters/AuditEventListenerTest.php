<?php

namespace Tests\OpenTelemetry\Formatters;

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

use App\Audit\AuditEventListener;
use App\Audit\Interfaces\IAuditStrategy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use Doctrine\ORM\PersistentCollection;
use Tests\TestCase;

class AuditEventListenerTest extends TestCase
{
    public function testAuditCollectionNonManyToManyReturnsOwner(): void
    {
        $listener = new AuditEventListener();
        $owner = new \stdClass();

        $mapping = OneToManyAssociationMapping::fromMappingArray([
            'fieldName' => 'items',
            'sourceEntity' => \stdClass::class,
            'targetEntity' => \stdClass::class,
            'mappedBy' => 'owner',
            'isOwningSide' => false,
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $meta = new ClassMetadata(\stdClass::class);
        $collection = new PersistentCollection($em, $meta, new ArrayCollection());
        $collection->setOwner($owner, $mapping);

        $method = new \ReflectionMethod(AuditEventListener::class, 'auditCollection');
        $method->setAccessible(true);

        [$subject, $payload, $eventType] = $method->invoke(
            $listener,
            $collection,
            new \stdClass(),
            IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE
        );

        $this->assertSame($owner, $subject);
        $this->assertSame([], $payload);
        $this->assertSame(IAuditStrategy::EVENT_COLLECTION_UPDATE, $eventType);
    }

    public function testFetchManyToManyIdsExecutesQuery(): void
    {
        $listener = new AuditEventListener();
        $owner = new \stdClass();

        $mapping = ManyToManyOwningSideMapping::fromMappingArrayAndNamingStrategy([
            'fieldName' => 'tags',
            'sourceEntity' => \stdClass::class,
            'targetEntity' => \stdClass::class,
            'isOwningSide' => true,
            'joinTable' => [
                'name' => 'owner_tags',
                'joinColumns' => [['name' => 'owner_id', 'referencedColumnName' => 'id']],
                'inverseJoinColumns' => [['name' => 'tag_id', 'referencedColumnName' => 'id']],
            ],
        ], new DefaultNamingStrategy());

        $em = $this->createMock(EntityManagerInterface::class);
        $meta = new ClassMetadata(\stdClass::class);
        $collection = new PersistentCollection($em, $meta, new ArrayCollection());
        $collection->setOwner($owner, $mapping);

        $meta = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $meta->method('getIdentifierValues')->with($owner)->willReturn(['id' => 123]);

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'from', 'where', 'setParameter', 'getSQL', 'fetchFirstColumn'])
            ->getMock();
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getSQL')->willReturn('SELECT tag_id FROM owner_tags WHERE owner_id = :ownerId');
        $qb->method('fetchFirstColumn')->willReturn(['10', '11']);

        $conn = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $conn->method('createQueryBuilder')->willReturn($qb);

        $em->method('getConnection')->willReturn($conn);
        $em->method('getClassMetadata')->with(get_class($owner))->willReturn($meta);

        $emProp = new \ReflectionProperty(AuditEventListener::class, 'em');
        $emProp->setAccessible(true);
        $emProp->setValue($listener, $em);

        $method = new \ReflectionMethod(AuditEventListener::class, 'fetchManyToManyIds');
        $method->setAccessible(true);

        $result = $method->invoke($listener, $collection, new \stdClass());

        $this->assertSame([10, 11], $result);
    }
}
