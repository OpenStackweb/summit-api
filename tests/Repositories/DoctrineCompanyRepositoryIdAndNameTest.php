<?php namespace Tests\Repositories;
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

use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\Registry;
use models\main\Company;
use models\main\ICompanyRepository;
use models\utils\SilverstripeBaseModel;
use Tests\TestCase;

/**
 * Class DoctrineCompanyRepositoryIdAndNameTest
 * @package Tests\Repositories
 */
final class DoctrineCompanyRepositoryIdAndNameTest extends TestCase
{
    private $em;

    /**
     * @var Company[]
     */
    private $companies = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!$this->em->isOpen()) {
            $this->em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->companies as $company) {
            $managed = $this->em->find(Company::class, $company->getId());
            if (!is_null($managed)) {
                $this->em->remove($managed);
            }
        }
        $this->em->flush();
        parent::tearDown();
    }

    public function testGetAllIdAndNameReturnsScalarPairs(): void
    {
        foreach (['A', 'B'] as $suffix) {
            $company = new Company();
            $company->setName('Company IdName ' . $suffix . ' ' . str_random(16));
            $this->em->persist($company);
            $this->companies[] = $company;
        }
        $this->em->flush();

        $repo = App::make(ICompanyRepository::class);
        $rows = $repo->getAllIdAndName();

        foreach ($this->companies as $company) {
            $this->assertContains(['id' => $company->getId(), 'name' => $company->getName()], $rows);
        }
        foreach ($rows as $row) {
            $this->assertIsInt($row['id']);
        }
    }
}
