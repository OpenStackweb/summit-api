<?php namespace repositories\main;
/**
 * Copyright 2021 OpenStack Foundation
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
use App\Models\Foundation\Main\Repositories\ILegalDocumentRepository;
use LaravelDoctrine\ORM\Facades\Registry;
use Libs\Utils\Doctrine\DoctrineStatementValueBinder;
use models\main\LegalDocument;
use models\utils\SilverstripeBaseModel;
/**
 * Class DoctrineLegalDocumentRepository
 * @package repositories\main
 */
final class DoctrineLegalDocumentRepository implements ILegalDocumentRepository {
  /**
   * @param string $slug
   * @return LegalDocument|null
   */
  public function getBySlug(string $slug): ?LegalDocument {
    try {
      $sql = <<<SQL
      select ID,Title,Content,URLSegment FROM SiteTree
      where SiteTree.URLSegment = :url_segment AND SiteTree.ClassName = :class_name
      SQL;
      $stmt = DoctrineStatementValueBinder::bind(
        Registry::getManager(SilverstripeBaseModel::EntityManager)
          ->getConnection()
          ->prepare($sql),
        [
          "url_segment" => trim($slug),
          "class_name" => "LegalDocumentPage",
        ],
      );
      $res = $stmt->executeQuery();
      $res = $res->fetchAllAssociative();
      if (count($res) == 0) {
        return null;
      }
      return new LegalDocument(
        $res[0]["ID"],
        trim($res[0]["Title"]),
        trim($res[0]["URLSegment"]),
        trim($res[0]["Content"]),
      );
    } catch (\Exception $ex) {
      return null;
    }
  }

  /**
   * @param int $id
   * @return LegalDocument|null
   */
  public function getById(int $id): ?LegalDocument {
    try {
      $sql = <<<SQL
      select ID,Title,Content,URLSegment FROM SiteTree
      where SiteTree.ID = :id AND SiteTree.ClassName = :class_name
      SQL;
      $stmt = DoctrineStatementValueBinder::bind(
        Registry::getManager(SilverstripeBaseModel::EntityManager)
          ->getConnection()
          ->prepare($sql),
        [
          "id" => $id,
          "class_name" => "LegalDocumentPage",
        ],
      );
      $res = $stmt->executeQuery();
      $res = $res->fetchAllAssociative();
      if (count($res) == 0) {
        return null;
      }
      return new LegalDocument(
        $res[0]["ID"],
        trim($res[0]["Title"]),
        trim($res[0]["URLSegment"]),
        trim($res[0]["Content"]),
      );
    } catch (\Exception $ex) {
      return null;
    }
  }
}
