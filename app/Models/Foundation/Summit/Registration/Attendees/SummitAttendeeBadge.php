<?php namespace models\summit;
/**
 * Copyright 2019 OpenStack Foundation
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

use App\Utils\AES;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Group;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitAttendeeBadge')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitAttendeeBadgeRepository::class)]
class SummitAttendeeBadge extends SilverstripeBaseModel implements IQREntity
{

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getTicketId' => 'ticket',
        'getTypeId'   => 'type',
    ];

    protected $hasPropertyMappings = [
        'hasTicket' => 'ticket',
        'hasType'   => 'type',
    ];


    /**
     * @var boolean
     */
    #[ORM\Column(name: 'IsVoid', type: 'boolean')]
    private $is_void;

    /**
     * @var string
     */
    #[ORM\Column(name: 'QRCode', type: 'string', nullable: true)]
    private $qr_code;

    /**
     * @var SummitAttendeeTicket
     */
    #[ORM\JoinColumn(name: 'TicketID', referencedColumnName: 'ID')]
    #[ORM\OneToOne(targetEntity: \SummitAttendeeTicket::class, inversedBy: 'badge')]
    private $ticket;

    /**
     * @var SummitBadgeType
     */
    #[ORM\JoinColumn(name: 'BadgeTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \SummitBadgeType::class)]
    private $type;

    /**
     * @var SummitBadgeFeatureType[]
     */
    #[ORM\JoinTable(name: 'SummitAttendeeBadge_Features')]
    #[ORM\JoinColumn(name: 'SummitAttendeeBadgeID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'SummitBadgeFeatureTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \SummitBadgeFeatureType::class)]
    private $features;

    /**
     * @var SummitAttendeeBadgePrint[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\SummitAttendeeBadgePrint::class, mappedBy: 'badge', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $prints;

    /**
     * SummitAttendeeBadge constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->features      = new ArrayCollection();
        $this->prints        = new ArrayCollection();
        $this->is_void       = false;
    }

    public function getQRCode(): ?string
    {
       return $this->qr_code;
    }

    use QRGeneratorTrait;

    /**
     * @return string
     * @throws ValidationException
     */
    public function generateQRCode(): string
    {
        Log::debug(sprintf("SummitAttendeeBadge::generateQRCode for id %s", $this->id));
        $ticket = $this->getTicket();
        if(is_null($ticket))
            throw new ValidationException("Ticket is not set.");

        $order  = $ticket->getOrder();
        if(is_null($order))
            throw new ValidationException("Order is not set.");

        $summit = $order->getSummit();
        if(is_null($summit))
            throw new ValidationException("Summit is not set.");

        $this->qr_code = $this->generateQRFromFields([
            $summit->getBadgeQRPrefix(),
            $ticket->getNumber(),
            $ticket->getOwnerEmail(),
            $ticket->getOwnerFullName(),
        ]);

        $qr_codes_enc_key = $summit->getQRCodesEncKey();
        if (!empty($qr_codes_enc_key)) {
            Log::debug(sprintf("SummitAttendeeBadge::generateQRCode encrypting qr_code %s for id %s", $this->qr_code, $this->id));
            $this->qr_code = AES::encrypt($qr_codes_enc_key, $this->qr_code);
        }

        Log::debug(sprintf("SummitAttendeeBadge::generateQRCode generated qr_code %s for id %s", $this->qr_code, $this->id));
        return $this->qr_code;
    }

    /**
     * @param string $qr_code
     * @return array
     * @throws ValidationException
     */
    static public function parseQRCode(string $qr_code):array{
        $fields = explode(IQREntity::QRRegistryFieldDelimiterChar, $qr_code);
        if(count($fields) != 4) throw new ValidationException("Invalid Badge QR code.");

        return [
            'prefix'         => $fields[0],
            'ticket_number'  => $fields[1],
            'owner_fullname' => $fields[3],
            'owner_email'    => $fields[2],
        ];
    }
    /**
     * @return bool
     */
    public function isVoid(): bool
    {
        return $this->is_void;
    }

    public function markVoid()
    {
        $this->is_void = true;
    }

    /**
     * @return SummitAttendeeTicket
     */
    public function getTicket(): SummitAttendeeTicket
    {
        return $this->ticket;
    }

    /**
     * @param SummitAttendeeTicket $ticket
     */
    public function setTicket(SummitAttendeeTicket $ticket): void
    {
        $this->ticket = $ticket;
    }

    /**
     * @return SummitBadgeType
     */
    public function getType(): SummitBadgeType
    {
        return $this->type;
    }

    /**
     * @param SummitBadgeType $type
     */
    public function setType(SummitBadgeType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return SummitBadgeFeatureType[]
     */
    public function getFeatures()
    {
        return $this->features;
    }

    /**
     * @return SummitAttendeeBadgePrint[]
     */
    public function getPrints()
    {
        return $this->prints;
    }

    /**
     * @return array
     */
    public function getAllFeatures(){
        $features = $this->features->toArray();
        $inherited_features = $this->type->getBadgeFeatures();
        foreach ($inherited_features as $inherited_feature){
            if($this->features->contains($inherited_feature)) continue;
            $features[] = $inherited_feature;
        }
        return $features;
    }

    public function clearFeatures():void{
        $this->features->clear();
    }

    /**
     * @param SummitBadgeFeatureType $feature
     * @return bool
     */
    public function hasFeature(SummitBadgeFeatureType $feature):bool{
        return $this->features->contains($feature);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasFeatureByName(string $name):bool{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $res = $this->features->matching($criteria)->count() > 0;
        if(!$res)
            $res = $this->type->hasFeatureByName($name);
        return $res;
    }

    /**
     * @param SummitBadgeFeatureType $feature
     * @return bool
     */
    public function isInheritedFeature(SummitBadgeFeatureType $feature):bool{
        $inherited_feature = $this->type->getBadgeFeatureById($feature->getId());
        if(!is_null($inherited_feature) && !$this->features->contains($inherited_feature)){
            return true;
        }
        return false;
    }

    public function addFeature(SummitBadgeFeatureType $feature){
        if($this->features->contains($feature)) return;
        $this->features->add($feature);
    }

    public function removeFeature(SummitBadgeFeatureType $feature){
        if(!$this->features->contains($feature)) return;
        $this->features->removeElement($feature);
    }

    /**
     * @param SummitRegistrationPromoCode $promo_code
     * @return $this
     */
    public function applyPromoCode(SummitRegistrationPromoCode $promo_code){
        foreach ($promo_code->getBadgeFeatures() as $feature)
            $this->addFeature($feature);
        return $this;
    }

    /**
     * @param SummitTicketType $ticket_type
     * @return $this
     */
    public function applyTicketType(SummitTicketType $ticket_type){
        $this->setType($ticket_type->getBadgeType());
        return $this;
    }

    /**
     * @return bool
     */
    public function isPrinted():bool {
        return $this->prints->count() > 0;
    }

    /**
     * @param Member $requestor
     * @param SummitBadgeViewType $viewType
     * @return SummitAttendeeBadgePrint
     * @throws ValidationException
     */
    public function printIt(Member $requestor, SummitBadgeViewType $viewType):SummitAttendeeBadgePrint{
        if(!$this->ticket->hasOwner())
            throw new ValidationException("badge has not owner set");

        if($this->is_void){
            throw new ValidationException("badge is void");
        }
        $this->generateQRCode();
        $print = SummitAttendeeBadgePrint::build($this, $requestor, $viewType);
        $this->prints->add($print);
        return $print;
    }

    /**
     * @param Group $group
     * @return int
     */
    public function getPrintCountPerGroup(Group $group):int{
        if($this->prints->count() == 0) return 0;

        try {
            $sql = <<<SQL
            SELECT COUNT(DISTINCT(SummitAttendeeBadgePrint.ID)) AS Print_Count
            FROM SummitAttendeeBadgePrint
            INNER JOIN Member ON Member.ID = SummitAttendeeBadgePrint.RequestorID
            INNER JOIN Group_Members ON Member.ID = Group_Members.MemberID
            WHERE SummitAttendeeBadgePrint.BadgeID = :badge_id AND Group_Members.GroupID = :group_id
SQL;
            $stmt = $this->prepareRawSQL($sql, [
                'badge_id' => $this->id,
                'group_id' => $group->getId(),
            ]);
            $res= $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
            return count($res) > 0 ? $res[0] : 0;
        } catch (\Exception $ex) {

        }
        return 0;
    }

    public function getPrintDate():?\DateTime{
        if(!$this->isPrinted()) return null;
        // get last print date
        return $this->prints->last()->getPrintDate();
    }

    /**
     * @return int
     */
    public function getPrintedTimes(): ?int
    {
        return $this->prints->count();
    }

    public function getPrintExcerpt():array{
        $res = [];
        foreach($this->prints as $print){
            $viewType = null;
            if($print->hasViewType())
                $viewType = $print->getViewType();
            if(!is_null($viewType)){
                if(!isset($res[$viewType->getName()]))
                    $res[$viewType->getName()] = 0;
                $res[$viewType->getName()] = $res[$viewType->getName()] + 1;
            }
        }
        return $res;
    }

    public function backupPrints(): void
    {
        try {
            $sql = <<<SQL
INSERT INTO SummitAttendeeBadgePrintBackUp (Created, LastEdited, PrintDate, BadgeID, RequestorID, SummitBadgeViewTypeID) 
SELECT Created, LastEdited, PrintDate, BadgeID, RequestorID, SummitBadgeViewTypeID
FROM SummitAttendeeBadgePrint
WHERE BadgeID = :badge_id;
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(
                [
                    'badge_id' => $this->getId(),
                ]
            );
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    public function clearPrints()
    {
        return $this->prints->clear();
    }

    /**
     * @param Summit $summit
     * @param string $qr_code
     * @return string
     */
    public static function decodeQRCodeFor(Summit $summit, string $qr_code): string
    {
        $val = trim($qr_code);
        if(base64_decode($val, true) !== false){
            // if qr_code is base64 encoded, decode it
            Log::debug(sprintf("SummitAttendeeBadge::decodeQRCodeFor summit %s qr_code %s with base64 decode", $summit->getId(), $val));
            $val = base64_decode($val, true);
        }

        // check first for encryption ...
        if(
            !str_starts_with($val, $summit->getTicketQRPrefix()) &&
            !str_starts_with($val, $summit->getBadgeQRPrefix()) &&
            $summit->hasQRCodesEncKey()){
            Log::debug
            (
                sprintf
                (
                    "SummitAttendeeBadge::decodeQRCodeFor summit %s qr_code %s with encryption",
                    $summit->getId(),
                    $val
                )
            );

            $val = AES::decrypt($summit->getQRCodesEncKey(), $val)->getData();
        }

        return $val;
    }
}