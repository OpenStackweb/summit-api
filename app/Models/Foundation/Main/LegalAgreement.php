<?php namespace models\main;
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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity
 * @ORM\Table(name="`LegalAgreement`")
 * Class LegalAgreement
 * @package models\main
 */
class LegalAgreement extends SilverstripeBaseModel
{

    const Slug = 'the-openstack-foundation-individual-member-agreement';
    /**
     * @ORM\Column(name="Signature", type="string")
     * @var string
     */
    private $signature;

    /**
     * @ORM\Column(name="LegalDocumentPageID", type="integer")
     * @var int
     */
    private $document_id;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", inversedBy="legal_agreements")
     * @ORM\JoinColumn(name="MemberID", referencedColumnName="ID")
     * @var Member
     */
    private $owner;

    /**
     * @return string
     */
    public function getSignature(): ?string
    {
        return $this->signature;
    }

    /**
     * @param string $signature
     */
    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }

    /**
     * @return int
     */
    public function getDocumentId(): int
    {
        return $this->document_id;
    }

    /**
     * @param int $document_id
     */
    public function setDocumentId(int $document_id): void
    {
        $this->document_id = $document_id;
    }

    /**
     * @return Member
     */
    public function getOwner(): Member
    {
        return $this->owner;
    }

    /**
     * @param Member $owner
     */
    public function setOwner(Member $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * this is for legacy reasons
     * @param string $slug
     * @return int|null
     */
    public static function getLegalAgreementIDBySlug(string $slug):?int {
        try {
            $sql = <<<SQL
select ID FROM SiteTree
where SiteTree.URLSegment = :url_segment AND SiteTree.ClassName = :class_name
SQL;
            $stmt = self::prepareRawSQLStatic($sql);
            $stmt->execute([
                'url_segment' => trim($slug),
                'class_name' => "LegalDocumentPage"
            ]);
            $res = $stmt->fetchAll();
            if(count($res) == 0 ) return null;
            $id = intval($res[0]['ID']);
            return $id;

        } catch (\Exception $ex) {
            return null;
        }
        return null;
    }

}