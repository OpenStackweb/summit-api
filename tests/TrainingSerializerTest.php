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
use App\Models\Foundation\Marketplace\CompanyService;
use App\Models\Foundation\Marketplace\Project;
use App\Models\Foundation\Marketplace\TrainingCourse;
use App\Models\Foundation\Marketplace\TrainingCourseLevel;
use App\Models\Foundation\Marketplace\TrainingCoursePrerequisite;
use App\Models\Foundation\Marketplace\TrainingCourseSchedule;
use App\Models\Foundation\Marketplace\TrainingCourseScheduleTime;
use App\Models\Foundation\Marketplace\TrainingCourseType;
use App\Models\Foundation\Marketplace\TrainingService;
use App\ModelSerializers\Marketplace\ProjectSerializer;
use App\ModelSerializers\Marketplace\TrainingCourseLevelSerializer;
use App\ModelSerializers\Marketplace\TrainingCoursePrerequisiteSerializer;
use App\ModelSerializers\Marketplace\TrainingCourseScheduleSerializer;
use App\ModelSerializers\Marketplace\TrainingCourseScheduleTimeSerializer;
use App\ModelSerializers\Marketplace\TrainingCourseSerializer;
use App\ModelSerializers\Marketplace\TrainingCourseTypeSerializer;
use App\ModelSerializers\Marketplace\TrainingServiceSerializer;
use models\oauth2\IResourceServerContext;
use Mockery;
use ModelSerializers\SerializerDecorator;

/**
 * Class TrainingSerializerTest
 * @package Tests
 */
final class TrainingSerializerTest extends TestCase
{
    /**
     * @var IResourceServerContext
     */
    private $resource_server_context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resource_server_context = Mockery::mock(IResourceServerContext::class);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper to create a base entity mock with standard SilverstripeBaseModel getters.
     */
    private function mockBaseEntity(string $class, int $id = 1): \Mockery\MockInterface
    {
        $mock = Mockery::mock($class)->makePartial();
        $mock->shouldReceive('getId')->andReturn($id);
        $mock->shouldReceive('getIdentifier')->andReturn($id);
        $mock->shouldReceive('getCreatedUTC')->andReturn(new \DateTime('2026-01-01 00:00:00'));
        $mock->shouldReceive('getLastEditedUTC')->andReturn(new \DateTime('2026-01-02 00:00:00'));
        return $mock;
    }

    // --- ProjectSerializer ---

    public function testProjectSerializer()
    {
        $project = $this->mockBaseEntity(Project::class, 10);
        $project->shouldReceive('getName')->andReturn('OpenStack Compute');
        $project->shouldReceive('getDescription')->andReturn('Compute service');
        $project->shouldReceive('getCodename')->andReturn('Nova');

        $serializer = new SerializerDecorator(new ProjectSerializer($project, $this->resource_server_context));
        $values = $serializer->serialize();

        $this->assertIsArray($values);
        $this->assertEquals(10, $values['id']);
        $this->assertEquals('OpenStack Compute', $values['name']);
        $this->assertEquals('Compute service', $values['description']);
        $this->assertEquals('Nova', $values['codename']);
    }

    // --- TrainingCourseTypeSerializer ---

    public function testTrainingCourseTypeSerializer()
    {
        $type = $this->mockBaseEntity(TrainingCourseType::class, 5);
        $type->shouldReceive('getType')->andReturn('Instructor Led');
        $type->shouldReceive('getClassName')->andReturn('TrainingCourseType');

        $serializer = new SerializerDecorator(new TrainingCourseTypeSerializer($type, $this->resource_server_context));
        $values = $serializer->serialize();

        $this->assertIsArray($values);
        $this->assertEquals(5, $values['id']);
        $this->assertEquals('Instructor Led', $values['type']);
    }

    // --- TrainingCourseLevelSerializer ---

    public function testTrainingCourseLevelSerializer()
    {
        $level = $this->mockBaseEntity(TrainingCourseLevel::class, 3);
        $level->shouldReceive('getLevel')->andReturn('Intermediate');
        $level->shouldReceive('getOrder')->andReturn(2);
        $level->shouldReceive('getClassName')->andReturn('TrainingCourseLevel');

        $serializer = new SerializerDecorator(new TrainingCourseLevelSerializer($level, $this->resource_server_context));
        $values = $serializer->serialize();

        $this->assertIsArray($values);
        $this->assertEquals(3, $values['id']);
        $this->assertEquals('Intermediate', $values['level']);
        $this->assertEquals(2, $values['order']);
    }

    // --- TrainingCoursePrerequisiteSerializer ---

    public function testTrainingCoursePrerequisiteSerializer()
    {
        $prerequisite = $this->mockBaseEntity(TrainingCoursePrerequisite::class, 7);
        $prerequisite->shouldReceive('getName')->andReturn('Basic Linux Skills');
        $prerequisite->shouldReceive('getClassName')->andReturn('TrainingCoursePrerequisite');

        $serializer = new SerializerDecorator(new TrainingCoursePrerequisiteSerializer($prerequisite, $this->resource_server_context));
        $values = $serializer->serialize();

        $this->assertIsArray($values);
        $this->assertEquals(7, $values['id']);
        $this->assertEquals('Basic Linux Skills', $values['name']);
    }

    // --- TrainingCourseScheduleTimeSerializer ---

    public function testTrainingCourseScheduleTimeSerializer()
    {
        $time = $this->mockBaseEntity(TrainingCourseScheduleTime::class, 20);
        $time->shouldReceive('getStartDate')->andReturn(new \DateTime('2026-03-01 09:00:00'));
        $time->shouldReceive('getEndDate')->andReturn(new \DateTime('2026-03-01 17:00:00'));
        $time->shouldReceive('getLink')->andReturn('https://example.com/register');
        $time->shouldReceive('getLocationId')->andReturn(50);
        $time->shouldReceive('getClassName')->andReturn('TrainingCourseScheduleTime');

        $serializer = new SerializerDecorator(new TrainingCourseScheduleTimeSerializer($time, $this->resource_server_context));
        $values = $serializer->serialize();

        $this->assertIsArray($values);
        $this->assertEquals(20, $values['id']);
        $this->assertIsInt($values['start_date']);
        $this->assertIsInt($values['end_date']);
        $this->assertEquals('https://example.com/register', $values['link']);
        $this->assertEquals(50, $values['location_id']);
    }

    // --- TrainingCourseScheduleSerializer ---

    public function testTrainingCourseScheduleSerializer()
    {
        $time1 = $this->mockBaseEntity(TrainingCourseScheduleTime::class, 20);
        $time2 = $this->mockBaseEntity(TrainingCourseScheduleTime::class, 21);

        $schedule = $this->mockBaseEntity(TrainingCourseSchedule::class, 50);
        $schedule->shouldReceive('getCity')->andReturn('Austin');
        $schedule->shouldReceive('getState')->andReturn('TX');
        $schedule->shouldReceive('getCountry')->andReturn('US');
        $schedule->shouldReceive('getTimes')->andReturn([$time1, $time2]);
        $schedule->shouldReceive('getClassName')->andReturn('TrainingCourseSchedule');

        $serializer = new SerializerDecorator(new TrainingCourseScheduleSerializer($schedule, $this->resource_server_context));
        $values = $serializer->serialize();

        $this->assertIsArray($values);
        $this->assertEquals(50, $values['id']);
        $this->assertEquals('Austin', $values['city']);
        $this->assertEquals('TX', $values['state']);
        $this->assertEquals('US', $values['country']);
        $this->assertIsArray($values['times']);
        $this->assertCount(2, $values['times']);
        $this->assertEquals([20, 21], $values['times']);
    }

    // --- TrainingCourseSerializer ---

    public function testTrainingCourseSerializer()
    {
        $schedule1 = $this->mockBaseEntity(TrainingCourseSchedule::class, 50);
        $project1 = $this->mockBaseEntity(Project::class, 10);
        $prereq1 = $this->mockBaseEntity(TrainingCoursePrerequisite::class, 7);

        $course = $this->mockBaseEntity(TrainingCourse::class, 100);
        $course->shouldReceive('getName')->andReturn('OpenStack Administration');
        $course->shouldReceive('getLink')->andReturn('https://example.com/course');
        $course->shouldReceive('getDescription')->andReturn('Admin course');
        $course->shouldReceive('isPaid')->andReturn(true);
        $course->shouldReceive('isOnline')->andReturn(false);
        $course->shouldReceive('getTypeId')->andReturn(5);
        $course->shouldReceive('getLevelId')->andReturn(3);
        $course->shouldReceive('getTrainingServiceId')->andReturn(200);
        $course->shouldReceive('getSchedules')->andReturn([$schedule1]);
        $course->shouldReceive('getProjects')->andReturn([$project1]);
        $course->shouldReceive('getPrerequisites')->andReturn([$prereq1]);
        $course->shouldReceive('getClassName')->andReturn('TrainingCourse');

        $serializer = new SerializerDecorator(new TrainingCourseSerializer($course, $this->resource_server_context));
        $values = $serializer->serialize();

        $this->assertIsArray($values);
        $this->assertEquals(100, $values['id']);
        $this->assertEquals('OpenStack Administration', $values['name']);
        $this->assertEquals('https://example.com/course', $values['link']);
        $this->assertEquals('Admin course', $values['description']);
        $this->assertTrue($values['is_paid']);
        $this->assertFalse($values['is_online']);
        $this->assertEquals(5, $values['type_id']);
        $this->assertEquals(3, $values['level_id']);
        $this->assertEquals(200, $values['training_service_id']);
        $this->assertEquals([50], $values['schedules']);
        $this->assertEquals([10], $values['projects']);
        $this->assertEquals([7], $values['prerequisites']);
    }

    public function testTrainingCourseSerializerWithoutRelations()
    {
        $course = $this->mockBaseEntity(TrainingCourse::class, 100);
        $course->shouldReceive('getName')->andReturn('OpenStack Administration');
        $course->shouldReceive('getLink')->andReturn('https://example.com/course');
        $course->shouldReceive('getDescription')->andReturn('Admin course');
        $course->shouldReceive('isPaid')->andReturn(true);
        $course->shouldReceive('isOnline')->andReturn(false);
        $course->shouldReceive('getTypeId')->andReturn(5);
        $course->shouldReceive('getLevelId')->andReturn(3);
        $course->shouldReceive('getTrainingServiceId')->andReturn(200);
        $course->shouldReceive('getClassName')->andReturn('TrainingCourse');

        $serializer = new SerializerDecorator(new TrainingCourseSerializer($course, $this->resource_server_context));
        // pass empty relations to skip collection serialization
        $values = $serializer->serialize(null, [], []);

        $this->assertIsArray($values);
        $this->assertEquals(100, $values['id']);
        $this->assertEquals('OpenStack Administration', $values['name']);
        $this->assertArrayNotHasKey('schedules', $values);
        $this->assertArrayNotHasKey('projects', $values);
        $this->assertArrayNotHasKey('prerequisites', $values);
    }

    // --- TrainingServiceSerializer ---

    public function testTrainingServiceSerializer()
    {
        $course1 = $this->mockBaseEntity(TrainingCourse::class, 100);
        $course2 = $this->mockBaseEntity(TrainingCourse::class, 101);

        $service = $this->mockBaseEntity(TrainingService::class, 200);
        $service->shouldReceive('getClassName')->andReturn('TrainingService');
        $service->shouldReceive('getName')->andReturn('Cloud Training Inc.');
        $service->shouldReceive('getOverview')->andReturn('Training overview');
        $service->shouldReceive('getCall2ActionUrl')->andReturn('https://example.com/action');
        $service->shouldReceive('getSlug')->andReturn('cloud-training-inc');
        $service->shouldReceive('getCompanyId')->andReturn(1);
        $service->shouldReceive('getTypeId')->andReturn(2);
        $service->shouldReceive('getCourses')->andReturn([$course1, $course2]);
        // parent CompanyServiceSerializer relations
        $service->shouldReceive('getCaseStudies')->andReturn([]);
        $service->shouldReceive('getVideos')->andReturn([]);
        $service->shouldReceive('getApprovedReviews')->andReturn([]);
        $service->shouldReceive('getResources')->andReturn([]);

        $serializer = new SerializerDecorator(new TrainingServiceSerializer($service, $this->resource_server_context));
        $values = $serializer->serialize();

        $this->assertIsArray($values);
        $this->assertEquals(200, $values['id']);
        $this->assertEquals('Cloud Training Inc.', $values['name']);
        $this->assertEquals('Training overview', $values['overview']);
        $this->assertEquals('cloud-training-inc', $values['slug']);
        $this->assertEquals(1, $values['company_id']);
        $this->assertEquals(2, $values['type_id']);
        $this->assertIsArray($values['courses']);
        $this->assertCount(2, $values['courses']);
        $this->assertEquals([100, 101], $values['courses']);
    }

    public function testTrainingServiceSerializerInheritsParentRelations()
    {
        $service = $this->mockBaseEntity(TrainingService::class, 200);
        $service->shouldReceive('getClassName')->andReturn('TrainingService');
        $service->shouldReceive('getName')->andReturn('Cloud Training Inc.');
        $service->shouldReceive('getOverview')->andReturn('Training overview');
        $service->shouldReceive('getCall2ActionUrl')->andReturn('https://example.com/action');
        $service->shouldReceive('getSlug')->andReturn('cloud-training-inc');
        $service->shouldReceive('getCompanyId')->andReturn(1);
        $service->shouldReceive('getTypeId')->andReturn(2);
        $service->shouldReceive('getCourses')->andReturn([]);
        $service->shouldReceive('getCaseStudies')->andReturn([]);
        $service->shouldReceive('getVideos')->andReturn([]);
        $service->shouldReceive('getApprovedReviews')->andReturn([]);
        $service->shouldReceive('getResources')->andReturn([]);

        $serializer = new SerializerDecorator(new TrainingServiceSerializer($service, $this->resource_server_context));
        $values = $serializer->serialize();

        $this->assertIsArray($values);
        // Parent CompanyServiceSerializer relations
        $this->assertArrayHasKey('case_studies', $values);
        $this->assertArrayHasKey('videos', $values);
        $this->assertArrayHasKey('reviews', $values);
        $this->assertArrayHasKey('resources', $values);
        // Own relation
        $this->assertArrayHasKey('courses', $values);
    }
}
