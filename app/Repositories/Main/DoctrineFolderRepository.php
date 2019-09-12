<?php namespace repositories\main;
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
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use models\main\File;
use models\main\IFolderRepository;
/**
 * Class DoctrineFolderRepository
 * @package repositories\main
 */
final class DoctrineFolderRepository
    extends SilverStripeDoctrineRepository
    implements IFolderRepository
{

    /**
     * @param string $folder_name
     * @return File
     */
    public function getFolderByName($folder_name)
    {

        $query = <<<SQL
      select * from File where ClassName = 'Folder' AND 
      Name = :folder_name LIMIT 0,1;
SQL;
        // build rsm here
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(\models\main\File::class, 'f');
        $native_query = $this->getEntityManager()->createNativeQuery($query, $rsm);

        $native_query->setParameter("folder_name", $folder_name);

        return $native_query->getOneOrNullResult();
    }

    /**
     * @param string $file_name
     * @return File
     */
    public function getFolderByFileName($file_name)
    {

        $query = <<<SQL
      select * from File where ClassName = 'Folder' AND 
      FileName = :file_name  LIMIT 0,1;
SQL;
        // build rsm here
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(\models\main\File::class, 'f');
        $native_query = $this->getEntityManager()->createNativeQuery($query, $rsm);

        $native_query->setParameter("file_name", $file_name);

        return $native_query->getOneOrNullResult();
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return File::class;
    }

    /**
     * @param string $folder_name
     * @param File $parent
     * @return File
     */
    public function getFolderByNameAndParent($folder_name, File $parent)
    {
        $query = <<<SQL
      select * from File where ClassName = 'Folder' AND 
      Name = :folder_name and ParentID = :parent_id LIMIT 0,1;
SQL;
        // build rsm here
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(\models\main\File::class, 'f');
        $native_query = $this->getEntityManager()->createNativeQuery($query, $rsm);

        $native_query->setParameter("folder_name", $folder_name);
        $native_query->setParameter("parent_id", $parent->getId());

        return $native_query->getOneOrNullResult();
    }
}