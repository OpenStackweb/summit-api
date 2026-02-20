<?php

namespace Tests\Unit\Repositories;

use App\Repositories\Summit\DoctrineSummitEventRepository;
use PHPUnit\Framework\TestCase;

class SummitEventExpandMapTest extends TestCase
{
    private array $map;

    protected function setUp(): void
    {
        parent::setUp();
        $this->map = DoctrineSummitEventRepository::getExpandFieldMap();
    }

    /**
     * Every entry in the field map must have a non-empty Doctrine field name.
     */
    public function testAllFieldMapEntriesHaveNonEmptyValues(): void
    {
        foreach ($this->map as $expandName => $doctrineField) {
            $this->assertIsString($doctrineField, "Field map entry '{$expandName}' must be a string");
            $this->assertNotEmpty($doctrineField, "Field map entry '{$expandName}' must not be empty");
        }
    }

    /**
     * The field map must only contain entries where the serializer name
     * differs from the Doctrine field name. If they're the same, the entry is redundant.
     */
    public function testFieldMapOnlyContainsMismatches(): void
    {
        foreach ($this->map as $expandName => $doctrineField) {
            $this->assertNotSame(
                $expandName,
                $doctrineField,
                "Field map entry '{$expandName}' => '{$doctrineField}' is redundant — name matches Doctrine field"
            );
        }
    }

    /**
     * Known mismatches must be present in the field map.
     */
    public function testKnownMismatchesArePresent(): void
    {
        $expectedMismatches = [
            'track'              => 'category',
            'creator'            => 'created_by',
            'current_attendance' => 'attendance_metrics',
            'slides'             => 'materials',
            'videos'             => 'materials',
            'media_uploads'      => 'materials',
            'links'              => 'materials',
            'extra_questions'    => 'extra_question_answers',
            'public_comments'    => 'comments',
        ];

        foreach ($expectedMismatches as $expandName => $expectedField) {
            $this->assertArrayHasKey(
                $expandName,
                $this->map,
                "Field map is missing known mismatch: {$expandName}"
            );
            $this->assertSame(
                $expectedField,
                $this->map[$expandName],
                "Field map entry '{$expandName}' should map to '{$expectedField}'"
            );
        }
    }

    /**
     * The material-type expands should all map to the same 'materials' field.
     */
    public function testMaterialsFieldDeduplication(): void
    {
        $materialExpands = ['slides', 'videos', 'media_uploads', 'links'];
        $fields = [];
        foreach ($materialExpands as $expand) {
            $this->assertArrayHasKey($expand, $this->map, "Field map missing material expand: {$expand}");
            $fields[] = $this->map[$expand];
        }
        $this->assertCount(1, array_unique($fields), "All material-type expands should map to the same 'materials' field");
        $this->assertSame('materials', $fields[0]);
    }

    /**
     * Creator and created_by should resolve to the same Doctrine field.
     * 'creator' is in the field map; 'created_by' matches its Doctrine name so it's not.
     */
    public function testCreatorMapsToCreatedBy(): void
    {
        $this->assertArrayHasKey('creator', $this->map);
        $this->assertSame('created_by', $this->map['creator']);
    }

    /**
     * Empty field map filter should produce no entries.
     */
    public function testEmptyExpandsProduceNoEntries(): void
    {
        $expands = [];
        $matched = array_filter($this->map, fn($key) => in_array($key, $expands), ARRAY_FILTER_USE_KEY);
        $this->assertEmpty($matched);
    }
}
