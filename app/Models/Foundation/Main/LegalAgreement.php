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
 * @package models\main
 */
#[ORM\Table(name: '`LegalAgreement`')]
#[ORM\Entity]
class LegalAgreement extends SilverstripeBaseModel
{

    const Slug = 'the-openstack-foundation-individual-member-agreement';
    /**
     * @var string
     */
    #[ORM\Column(name: 'Signature', type: 'string')]
    private $signature;

    /**
     * @var int
     */
    #[ORM\Column(name: 'LegalDocumentPageID', type: 'integer')]
    private $document_id;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'MemberID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class, inversedBy: 'legal_agreements')]
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
     * @return int
     */
    public function getOwnerId(){
        try {
            return $this->owner->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @param LegalDocument $document
     */
    public function setDocument(LegalDocument $document): void
    {
        $this->document_id = $document->getId();
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

    public function getContent():?String{

    }

    public function getTitle():?string{

    }

}