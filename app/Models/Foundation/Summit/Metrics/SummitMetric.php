<?php namespace models\summit;
/**
 * Copyright 2020 OpenStack Foundation
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
use App\Services\Utils\UserClientHelper;
use models\exceptions\ValidationException;
use models\main\Member;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitMetricRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="metrics"
 *     )
 * })
 * @ORM\Table(name="SummitMetric")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="ClassName", type="string")
 * @ORM\DiscriminatorMap({
 *     "SummitMetric" = "SummitMetric",
 *     "SummitEventAttendanceMetric" = "SummitEventAttendanceMetric",
 *     "SummitSponsorMetric" = "SummitSponsorMetric"
 * })
 * Class SummitMetric
 * @package models\summit
 */
class SummitMetric extends SilverstripeBaseModel
{
    use SummitOwned;

    /**
     * @ORM\Column(name="IngressDate", type="datetime")
     * @var \DateTime
     */
    protected $ingress_date;

    /**
     * @ORM\Column(name="OutgressDate", type="datetime")
     * @var \DateTime|null
     */
    protected $outgress_date;

    /**
     * @ORM\Column(name="Ip", type="string")
     * @var string|null
     */
    protected $ip;

    /**
     * @ORM\Column(name="Type", type="string")
     * @var string|null
     */
    protected $type;

    /**
     * @ORM\Column(name="Origin", type="string")
     * @var string|null
     */
    protected $origin;

    /**
     * @ORM\Column(name="Location", type="string")
     * @var string|null
     */
    protected $location;

    /**
     * @ORM\Column(name="Browser", type="string")
     * @var string|null
     */
    protected $browser;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", inversedBy="summit_attendance_metrics")
     * @ORM\JoinColumn(name="MemberID", referencedColumnName="ID", onDelete="CASCADE")
     * @var Member|null
     */
    protected $member;

    const AccessTypeIngress = 'INGRESS';
    const AccessTypeEgress = 'EGRESS';

    const ValidAccessTypes = [
        self::AccessTypeIngress,
        self::AccessTypeEgress,
    ];

    /**
     * @return \DateTime
     */
    public function getIngressDate(): \DateTime
    {
        return $this->ingress_date;
    }

    /**
     * @param \DateTime $ingress_date
     */
    public function setIngressDate(\DateTime $ingress_date): void
    {
        $this->ingress_date = $ingress_date;
    }

    /**
     * @return \DateTime
     */
    public function getOutgressDate(): ?\DateTime
    {
        return $this->outgress_date;
    }

    /**
     * @param \DateTime $outgress_date
     */
    public function setOutgressDate(\DateTime $outgress_date): void
    {
        $this->outgress_date = $outgress_date;
    }

    /**
     * @return Member
     */
    public function getMember(): ?Member
    {
        return $this->member;
    }

    /**
     * @param Member $member
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    /**
     * @throws ValidationException
     */
    public function abandon(){
        if(is_null($this->ingress_date))
            throw new ValidationException('You must enter first.');
        $this->outgress_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function getMemberFirstName():?string{
        return $this->member->getFirstName();
    }

    public function getMemberLastName():?string{
        return $this->member->getLastName();
    }

    public function getMemberProfilePhotoUrl():?string{
        return $this->member->getProfilePhotoUrl();
    }

    /**
     * @param Member|null $member
     * @return SummitMetric
     * @throws \Exception
     */
    public static function build(?Member $member = null){
        $metric = new static();
        $metric->member = $member;
        $metric->ingress_date = new \DateTime('now', new \DateTimeZone('UTC'));
        $metric->ip = UserClientHelper::getUserIp();
        $metric->origin = UserClientHelper::getUserOrigin();
        $metric->browser = UserClientHelper::getUserBrowser();
        return $metric;
    }

    /**
     * @return int
     */
    public function getMemberId(){
        try {
            return is_null($this->member) ? 0 : $this->member->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasMember():bool{
        return $this->getMemberId() > 0;
    }

    public function clearMember(){
        $this->member = null;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     * @throws ValidationException
     */
    public function setType(?string $type): void
    {
        if(!in_array($type, ISummitMetricType::ValidTypes))
            throw new ValidationException(sprintf("Type %s is not a valid one.", $type));
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    /**
     * @return string|null
     */
    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    /**
     * @return string|null
     */
    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    /**
     * @return string|null
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * @param string|null $location
     */
    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }

}