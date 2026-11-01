<?php namespace Tests\Unit\Marketplace;

use App\Models\Foundation\Marketplace\CompanyService;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use PHPUnit\Framework\TestCase;

final class CompanyServiceMappingTest extends TestCase
{
    public function testResourceOrderingLoadsAsValidDoctrineMetadata(): void
    {
        $metadata = new ClassMetadata(CompanyService::class);
        $metadata->initializeReflection(new RuntimeReflectionService());
        $driver = new AttributeDriver([]);
        $driver->loadMetadataForClass(CompanyService::class, $metadata);

        $ordering = $metadata->getAssociationMapping('resources')->orderBy;
        self::assertSame(['order'], array_keys($ordering));
        self::assertSame(\SortDirection::Ascending, $ordering['order']);
    }
}
