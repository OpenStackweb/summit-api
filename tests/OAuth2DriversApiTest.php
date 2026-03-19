<?php namespace Tests;
/**
 * Copyright 2017 OpenStack Foundation
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
use App\Models\Foundation\Marketplace\Driver;
use App\Models\Foundation\Marketplace\DriverRelease;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;

/**
 * Class OAuth2DriversApiTest
 * @package Tests
 */
final class OAuth2DriversApiTest extends BrowserKitTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    static $em;

    /**
     * @var Driver
     */
    static $driver1;

    /**
     * @var Driver
     */
    static $driver2;

    /**
     * @var Driver
     */
    static $inactiveDriver;

    /**
     * @var DriverRelease
     */
    static $release1;

    /**
     * @var DriverRelease
     */
    static $release2;

    protected function setUp(): void
    {
        parent::setUp();

        DB::setDefaultConnection("model");

        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!self::$em->isOpen()) {
            self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }

        // clean up any leftover test data
        self::clearDriverTestData();

        // create releases
        self::$release1 = new DriverRelease();
        self::$release1->setName('Rocky');
        self::$release1->setUrl('https://releases.openstack.org/rocky');
        self::$release1->setActive(true);

        self::$release2 = new DriverRelease();
        self::$release2->setName('Stein');
        self::$release2->setUrl('https://releases.openstack.org/stein');
        self::$release2->setActive(true);

        self::$em->persist(self::$release1);
        self::$em->persist(self::$release2);

        // create active drivers
        self::$driver1 = new Driver();
        self::$driver1->setName('Test Driver Nova');
        self::$driver1->setDescription('A test nova driver');
        self::$driver1->setProject('Nova');
        self::$driver1->setVendor('TestVendorA');
        self::$driver1->setUrl('https://example.com/driver1');
        self::$driver1->setTested(true);
        self::$driver1->setActive(true);

        self::$driver2 = new Driver();
        self::$driver2->setName('Test Driver Cinder');
        self::$driver2->setDescription('A test cinder driver');
        self::$driver2->setProject('Cinder');
        self::$driver2->setVendor('TestVendorB');
        self::$driver2->setUrl('https://example.com/driver2');
        self::$driver2->setTested(false);
        self::$driver2->setActive(true);

        // create inactive driver (should not appear in results)
        self::$inactiveDriver = new Driver();
        self::$inactiveDriver->setName('Inactive Driver');
        self::$inactiveDriver->setProject('Nova');
        self::$inactiveDriver->setVendor('InactiveVendor');
        self::$inactiveDriver->setActive(false);

        // link releases to drivers
        self::$driver1->addRelease(self::$release1);
        self::$driver1->addRelease(self::$release2);
        self::$driver2->addRelease(self::$release1);

        self::$em->persist(self::$driver1);
        self::$em->persist(self::$driver2);
        self::$em->persist(self::$inactiveDriver);
        self::$em->flush();
    }

    protected function tearDown(): void
    {
        self::clearDriverTestData();
        parent::tearDown();
    }

    private static function clearDriverTestData(): void
    {
        // clean junction table first, then entities
        DB::connection('model')->delete("DELETE FROM Driver_Releases");
        DB::connection('model')->delete("DELETE FROM Driver WHERE Name LIKE 'Test Driver%' OR Name = 'Inactive Driver'");
        DB::connection('model')->delete("DELETE FROM DriverRelease WHERE Name IN ('Rocky','Stein')");
        if (isset(self::$em) && self::$em->isOpen()) {
            self::$em->clear();
        }
    }

    public function testGetAllDrivers()
    {
        $params = [
            'page'     => 1,
            'per_page' => 100,
        ];

        $response = $this->action(
            "GET",
            "DriversApiController@getAll",
            $params,
            [], [], [], []
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        // should contain at least our 2 active drivers, but NOT the inactive one
        $this->assertTrue($page->total >= 2);

        // verify inactive driver is not in results
        $names = array_map(function ($d) { return $d->name; }, $page->data);
        $this->assertNotContains('Inactive Driver', $names);
    }

    public function testGetAllDriversWithExpandReleases()
    {
        $params = [
            'page'     => 1,
            'per_page' => 100,
            'expand'   => 'releases',
        ];

        $response = $this->action(
            "GET",
            "DriversApiController@getAll",
            $params,
            [], [], [], []
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertTrue($page->total >= 2);

        // find our test driver1 and verify it has expanded releases
        $driver1 = null;
        foreach ($page->data as $d) {
            if ($d->name === 'Test Driver Nova') {
                $driver1 = $d;
                break;
            }
        }
        $this->assertNotNull($driver1);
        $this->assertTrue(is_array($driver1->releases));
        $this->assertTrue(count($driver1->releases) >= 2);
    }

    public function testGetDriversFilterByProject()
    {
        $params = [
            'page'     => 1,
            'per_page' => 100,
            'filter'   => 'project==Nova',
        ];

        $response = $this->action(
            "GET",
            "DriversApiController@getAll",
            $params,
            [], [], [], []
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertTrue($page->total >= 1);

        // all returned drivers should have project Nova
        foreach ($page->data as $d) {
            $this->assertEquals('Nova', $d->project);
        }
    }

    public function testGetDriversFilterByVendor()
    {
        $params = [
            'page'     => 1,
            'per_page' => 100,
            'filter'   => 'vendor@@TestVendorA',
        ];

        $response = $this->action(
            "GET",
            "DriversApiController@getAll",
            $params,
            [], [], [], []
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertTrue($page->total >= 1);

        foreach ($page->data as $d) {
            $this->assertStringContainsString('TestVendorA', $d->vendor);
        }
    }

    public function testGetDriversFilterByName()
    {
        $params = [
            'page'     => 1,
            'per_page' => 100,
            'filter'   => 'name=@Cinder',
        ];

        $response = $this->action(
            "GET",
            "DriversApiController@getAll",
            $params,
            [], [], [], []
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertTrue($page->total >= 1);

        foreach ($page->data as $d) {
            $this->assertStringContainsString('Cinder', $d->name);
        }
    }

    public function testGetDriversFilterByRelease()
    {
        $params = [
            'page'     => 1,
            'per_page' => 100,
            'filter'   => 'release==Rocky',
            'expand'   => 'releases',
        ];

        $response = $this->action(
            "GET",
            "DriversApiController@getAll",
            $params,
            [], [], [], []
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        // both test drivers have Rocky release
        $this->assertTrue($page->total >= 2);
    }

    public function testGetDriversOrderByName()
    {
        $params = [
            'page'     => 1,
            'per_page' => 100,
            'order'    => '+name',
        ];

        $response = $this->action(
            "GET",
            "DriversApiController@getAll",
            $params,
            [], [], [], []
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertTrue($page->total >= 2);

        // verify ascending order
        for ($i = 1; $i < count($page->data); $i++) {
            $this->assertTrue(
                strcmp($page->data[$i - 1]->name, $page->data[$i]->name) <= 0
            );
        }
    }

    public function testGetDriversOrderByProject()
    {
        $params = [
            'page'     => 1,
            'per_page' => 100,
            'order'    => '+project',
        ];

        $response = $this->action(
            "GET",
            "DriversApiController@getAll",
            $params,
            [], [], [], []
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertTrue($page->total >= 2);
    }

    public function testGetDriversResponseFields()
    {
        $params = [
            'page'     => 1,
            'per_page' => 100,
        ];

        $response = $this->action(
            "GET",
            "DriversApiController@getAll",
            $params,
            [], [], [], []
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));

        // find our test driver and verify serialized fields
        $driver = null;
        foreach ($page->data as $d) {
            if ($d->name === 'Test Driver Nova') {
                $driver = $d;
                break;
            }
        }
        $this->assertNotNull($driver);
        $this->assertEquals('Test Driver Nova', $driver->name);
        $this->assertEquals('A test nova driver', $driver->description);
        $this->assertEquals('Nova', $driver->project);
        $this->assertEquals('TestVendorA', $driver->vendor);
        $this->assertEquals('https://example.com/driver1', $driver->url);
        $this->assertTrue($driver->tested);
        $this->assertTrue($driver->active);
    }
}
