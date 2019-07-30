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
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;
use models\exceptions\ValidationException;
use models\main\Group;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitAttendeeBadgeRepository")
 * @ORM\Table(name="SummitAttendeeBadge")
 * Class SummitAttendeeBadge
 * @package models\summit
 */
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
     * @ORM\Column(name="IsVoid", type="boolean")
     * @var boolean
     */
    private $is_void;

    /**
     * @ORM\Column(name="QRCode", type="string", nullable=true)
     * @var string
     */
    private $qr_code;

    /**
     * @ORM\OneToOne(targetEntity="SummitAttendeeTicket", inversedBy="badge")
     * @ORM\JoinColumn(name="TicketID", referencedColumnName="ID")
     * @var SummitAttendeeTicket
     */
    private $ticket;

    /**
     * @ORM\ManyToOne(targetEntity="SummitBadgeType")
     * @ORM\JoinColumn(name="BadgeTypeID", referencedColumnName="ID")
     * @var SummitBadgeType
     */
    private $type;

    /**
     * @ORM\ManyToMany(targetEntity="SummitBadgeFeatureType")
     * @ORM\JoinTable(name="SummitAttendeeBadge_Features",
     *      joinColumns={@ORM\JoinColumn(name="SummitAttendeeBadgeID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SummitBadgeFeatureTypeID", referencedColumnName="ID")}
     *      )
     * @var SummitBadgeFeatureType[]
     */
    private $features;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitAttendeeBadgePrint", mappedBy="badge",  cascade={"persist","remove"}, orphanRemoval=true)
     */
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
        $ticket = $this->getTicket();
        if(is_null($ticket))
            throw new ValidationException("ticket is not set");

        $order  = $ticket->getOrder();
        if(is_null($order))
            throw new ValidationException("order is not set");

        $summit = $order->getSummit();
        if(is_null($summit))
            throw new ValidationException("summit is not set");

        $this->qr_code = $this->generateQRFromFields([
            $summit->getBadgeQRPrefix(),
            $ticket->getNumber(),
            $ticket->getOwnerEmail(),
            $ticket->getOwnerFullName(),
        ]);

        return $this->qr_code;
    }

    /**
     * @param string $qr_code
     * @return array
     * @throws ValidationException
     */
    static public function parseQRCode(string $qr_code):array{
        $fields = explode(IQREntity::QRRegistryFieldDelimiterChar, $qr_code);
        if(count($fields) != 4) throw new ValidationException("invalid qr code");

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
        $this->setType($promo_code->getBadgeType());
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
     * @return SummitAttendeeBadgePrint
     * @throws ValidationException
     */
    public function printIt(Member $requestor):SummitAttendeeBadgePrint{
        if(!$this->ticket->hasOwner())
            throw new ValidationException("badge has not owner set");

        if($this->is_void){
            throw new ValidationException("badge is void");
        }
        $this->generateQRCode();
        $print = SummitAttendeeBadgePrint::build($this, $requestor);
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
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute([
                'badge_id' => $this->id,
                'group_id' => $group->getId(),
            ]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
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

}