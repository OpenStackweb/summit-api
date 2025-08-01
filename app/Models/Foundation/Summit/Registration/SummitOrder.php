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

use App\Events\PaymentSummitRegistrationOrderConfirmed;
use App\libs\Utils\PunnyCodeHelper;
use App\Models\Utils\Traits\FinancialTrait;
use Doctrine\Common\Collections\Criteria;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use libs\utils\TextUtils;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\Member;
use models\utils\SilverstripeBaseModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitOrder')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitOrderRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'orders')])]
class SummitOrder extends SilverstripeBaseModel implements IQREntity
{
    use SummitOwned;

    use FinancialTrait;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Number', type: 'string')]
    private $number;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ExternalId', type: 'string')]
    private $external_id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Status', type: 'string')]
    private $status;

    /**
     * @var string
     */
    #[ORM\Column(name: 'PaymentMethod', type: 'string')]
    private $payment_method;

    /**
     * @var string
     */
    #[ORM\Column(name: 'QRCode', type: 'string', nullable: true)]
    private $qr_code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'OwnerFirstName', type: 'string')]
    private $owner_first_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'OwnerSurname', type: 'string')]
    private $owner_surname;

    /**
     * @var string
     */
    #[ORM\Column(name: 'OwnerEmail', type: 'string')]
    private $owner_email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'OwnerCompany', type: 'string')]
    private $owner_company_name;

    /**
     * @var Company
     */
    #[ORM\JoinColumn(name: 'OwnerCompanyID', referencedColumnName: 'ID', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \models\main\Company::class)]
    private $owner_company;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'OwnerID', referencedColumnName: 'ID', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class, inversedBy: 'summit_registration_orders')]
    private $owner;

    /**
     * @var string
     */
    #[ORM\Column(name: 'BillingAddress1', type: 'string')]
    private $billing_address_1;

    /**
     * @var string
     */
    #[ORM\Column(name: 'BillingAddress2', type: 'string')]
    private $billing_address_2;

    /**
     * @var string
     */
    #[ORM\Column(name: 'BillingAddressZipCode', type: 'string')]
    private $billing_address_zip_code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'BillingAddressCity', type: 'string')]
    private $billing_address_city;

    /**
     * @var string
     */
    #[ORM\Column(name: 'BillingAddressState', type: 'string')]
    private $billing_address_state;

    /**
     * @var string
     */
    #[ORM\Column(name: 'BillingAddressCountryISOCode', type: 'string')]
    private $billing_address_country_iso_code;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'ApprovedPaymentDate', type: 'datetime')]
    private $approved_payment_date;

    /**
     * @var string
     */
    #[ORM\Column(name: 'LastError', type: 'string')]
    private $last_error;

    /**
     * @var string
     */
    #[ORM\Column(name: 'PaymentGatewayClientToken', type: 'string')]
    private $payment_gateway_client_token;

    /**
     * @var string
     */
    #[ORM\Column(name: 'PaymentGatewayCartId', type: 'string')]
    private $payment_gateway_cart_id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Hash', type: 'string')]
    private $hash;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'HashCreationDate', type: 'datetime')]
    private $hash_creation_date;

    /**
     * @var SummitAttendeeTicket[]
     */
    #[ORM\OneToMany(targetEntity: \SummitAttendeeTicket::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $tickets;

    /**
     * @var SummitOrderExtraQuestionAnswer[]
     */
    #[ORM\OneToMany(targetEntity: \SummitOrderExtraQuestionAnswer::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $extra_question_answers;

    /**
     * @var \DateTime
     */
    private $disclaimer_accepted_date;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'LastReminderEmailSentDate', type: 'datetime')]
    private $last_reminder_email_sent_date;

    /**
     * @var string
     */
    #[ORM\Column(name: 'CreditCardType', type: 'string')]
    private $credit_card_type;

    /**
     * @var string
     */
    #[ORM\Column(name: 'CreditCard4Numbers', type: 'string')]
    private $credit_card_4numbers;

    /**
     * SummitOrder constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->tickets = new ArrayCollection();
        $this->extra_question_answers = new ArrayCollection();
        $this->status = IOrderConstants::ReservedStatus;
        $this->payment_method = IOrderConstants::OnlinePaymentMethod;
    }

    public function setPaymentMethodOffline()
    {
        $this->payment_method = IOrderConstants::OfflinePaymentMethod;
    }

    public function generateHash()
    {
        $email = $this->getOwnerEmail();
        if (empty($email))
            throw new ValidationException("owner email is null");

        $fname = $this->getOwnerFirstName();
        if (empty($fname))
            throw new ValidationException("owner first name is null");

        $lname = $this->getOwnerSurname();
        if (empty($lname))
            throw new ValidationException("owner last name is null");

        $token = $this->number . '.' . $email . '.' . $fname . "." . $lname;
        $token = $token . random_bytes(16) . time();
        $this->hash = hash('sha256', $token);
        $this->hash_creation_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function canPubliclyEdit(): bool
    {
        if (empty($this->hash) || is_null($this->hash_creation_date)) return false;
        $ttl_minutes = intval(Config::get("registration.order_public_edit_ttl", 10));
        Log::debug(sprintf("SummitOrder::canPubliclyEdit id %s ttl %s", $this->id, $ttl_minutes));
        $eol = new \DateTime('now', new \DateTimeZone('UTC'));
        $eol->sub(new \DateInterval('PT' . $ttl_minutes . 'M'));
        if ($this->hash_creation_date <= $eol) {
            Log::debug(sprintf("SummitOrder::canPubliclyEdit id %s ttl %s is void", $this->id, $ttl_minutes));
            return false;
        }
        Log::debug(sprintf("SummitOrder::canPubliclyEdit id %s ttl %s is valid", $this->id, $ttl_minutes));
        return true;
    }

    /**
     * @return string
     */
    public function generateNumber(): string
    {
        $this->number = strtoupper(str_replace(".", "", uniqid($this->summit->getOrderQRPrefix() . '_', true)));
        $this->generateQRCode();
        return $this->number;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @param string $number
     */
    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setPaidStatus()
    {
        $this->status = IOrderConstants::PaidStatus;
        $this->approved_payment_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function setPaid(array $payload = null)
    {
        Log::debug(sprintf("SummitOrder::setPaid order %s", $this->id));
        if ($this->isPaid()) {
            Log::warning(sprintf("SummitOrder %s is already Paid.", $this->getId()));
            return;
        }

        $this->setPaidStatus();

        foreach ($this->tickets as $ticket) {
            $ticket->setPaid();
        }

        if (!is_null($payload) && isset($payload['order_credit_card_type']) && isset($payload['order_credit_card_4numbers'])) {

            Log::debug
            (
                sprintf
                (
                    "SummitOrder::setPaid order %s setting credit card info %s",
                    $this->id,
                    json_encode($payload)
                )
            );

            $this->credit_card_type = $payload['order_credit_card_type'];
            $this->credit_card_4numbers = $payload['order_credit_card_4numbers'];
        }

        Event::dispatch(new PaymentSummitRegistrationOrderConfirmed($this->getId()));
    }

    /**
     * @param null|string $error
     */
    public function setPaymentError(?string $error): void
    {
        if (empty($error)) return;
        $this->status = IOrderConstants::ErrorStatus;
        $this->last_error = $error;
    }

    public function setConfirmed()
    {
        if ($this->status == IOrderConstants::ReservedStatus)
            $this->status = IOrderConstants::ConfirmedStatus;
    }


    public function setCancelled(): void
    {
        $ignore_statuses = [IOrderConstants::PaidStatus, IOrderConstants::CancelledStatus];

        if (in_array($this->status, $ignore_statuses)) return;
        $this->status = IOrderConstants::CancelledStatus;

        foreach ($this->getTickets() as $ticket) {
            $ticket->setCancelled();
        }
    }

    /**
     * @return array
     */
    public function calculateTicketsAndPromoCodesToReturn(): array
    {

        Log::debug(sprintf("SummitOrder::calculateTicketsAndPromoCodesToReturn order %s", $this->id));
        $tickets_to_return = [];
        $promo_codes_to_return = [];

        foreach ($this->tickets as $ticket) {

            if (!isset($tickets_to_return[$ticket->getTicketTypeId()]))
                $tickets_to_return[$ticket->getTicketTypeId()] = 0;

            $tickets_to_return[$ticket->getTicketTypeId()] += 1;

            Log::debug(sprintf("SummitOrder::calculateTicketsAndPromoCodesToReturn order %s ticket %s", $this->id, $ticket->getId()));

            if ($ticket->hasPromoCode()) {
                $code = $ticket->getPromoCode()->getCode();
                Log::debug(sprintf("SummitOrder::calculateTicketsAndPromoCodesToReturn order %s ticket %s promo code %s", $this->id, $ticket->getId(), $code));
                if (!isset($promo_codes_to_return[$code])) {
                    $promo_codes_to_return[$code] = [
                        "qty" => 0,
                        "owner_email" => $ticket->getOrder()->getOwnerEmail()
                    ];
                }
                $promo_codes_to_return[$code]["qty"] += 1;
            }
        }

        return [$tickets_to_return, $promo_codes_to_return];
    }

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->payment_method;
    }

    /**
     * @return bool
     */
    public function isOfflineOrder(): bool
    {
        return $this->payment_method == IOrderConstants::OfflinePaymentMethod;
    }

    /**
     * @param string $payment_method
     */
    public function setPaymentMethod(string $payment_method): void
    {
        if(in_array($payment_method, IOrderConstants::ValidPaymentMethods))
            throw new ValidationException(sprintf("payment method %s is not valid.", $payment_method));
        $this->payment_method = $payment_method;
    }

    /**
     * @return string
     */
    public function getQRCode(): ?string
    {
        return $this->qr_code;
    }

    /**
     * @return string
     */
    public function getOwnerFirstName(): ?string
    {
        $res = '';
        if ($this->hasOwner()) {
            $res = $this->owner->getFirstName();
        }
        if (empty($res))
            $res = $this->owner_first_name;
        return $res;
    }

    /**
     * @param string $owner_first_name
     */
    public function setOwnerFirstName(string $owner_first_name): void
    {
        $this->owner_first_name = TextUtils::trim($owner_first_name);
    }

    /**
     * @return string
     */
    public function getOwnerSurname(): ?string
    {
        $res = '';
        if ($this->hasOwner()) {
            $res = $this->owner->getLastName();
        }
        if (empty($res))
            $res = $this->owner_surname;
        return $res;
    }

    /**
     * @param string $owner_surname
     */
    public function setOwnerSurname(string $owner_surname): void
    {
        $this->owner_surname = TextUtils::trim($owner_surname);
    }

    /**
     * @return string
     */
    public function getOwnerEmail(): ?string
    {
        if (!is_null($this->owner)) {
            return $this->owner->getEmail();
        }
        return PunnyCodeHelper::decodeEmail($this->owner_email);
    }

    public function clearOwner(): void
    {
        $this->owner = null;
    }

    /**
     * @param string $owner_email
     */
    public function setOwnerEmail(string $owner_email): void
    {
        $this->owner_email = PunnyCodeHelper::encodeEmail($owner_email);
    }

    /**
     * @return string
     */
    public function getOwnerCompanyName(): ?string
    {
        if ($this->hasOwnerCompany())
            return $this->owner_company->getName();
        return $this->owner_company_name;
    }

    /**
     * @param string $owner_company_name
     */
    public function setOwnerCompanyName(string $owner_company_name): void
    {
        $this->owner_company_name = TextUtils::trim($owner_company_name);
    }

    /**
     * @return Company
     */
    public function getOwnerCompany(): ?Company
    {
        return $this->owner_company;
    }

    /**
     * @param Company $company
     */
    public function setOwnerCompany(Company $company): void
    {
        $this->owner_company = $company;
    }

    public function clearOwnerCompany():void{
        $this->owner_company = null;
    }

    /**
     * @return Member
     */
    public function getOwner(): ?Member
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
     * @return string
     */
    public function getBillingAddress1(): ?string
    {
        return $this->billing_address_1;
    }

    /**
     * @param string $billing_address_1
     */
    public function setBillingAddress1(string $billing_address_1): void
    {
        $this->billing_address_1 = $billing_address_1;
    }

    /**
     * @return string
     */
    public function getBillingAddress2(): ?string
    {
        return $this->billing_address_2;
    }

    /**
     * @param string $billing_address_2
     */
    public function setBillingAddress2(string $billing_address_2): void
    {
        $this->billing_address_2 = $billing_address_2;
    }

    /**
     * @return string
     */
    public function getBillingAddressZipCode(): ?string
    {
        return $this->billing_address_zip_code;
    }

    /**
     * @param string $billing_address_zip_code
     */
    public function setBillingAddressZipCode(string $billing_address_zip_code): void
    {
        $this->billing_address_zip_code = $billing_address_zip_code;
    }

    /**
     * @return string
     */
    public function getBillingAddressCity(): ?string
    {
        return $this->billing_address_city;
    }

    /**
     * @param string $billing_address_city
     */
    public function setBillingAddressCity(string $billing_address_city): void
    {
        $this->billing_address_city = $billing_address_city;
    }

    /**
     * @return string
     */
    public function getBillingAddressState(): ?string
    {
        return $this->billing_address_state;
    }

    /**
     * @param string $billing_address_state
     */
    public function setBillingAddressState(string $billing_address_state): void
    {
        $this->billing_address_state = $billing_address_state;
    }

    /**
     * @return string
     */
    public function getBillingAddressCountryIsoCode(): ?string
    {
        return $this->billing_address_country_iso_code;
    }

    /**
     * @param string $billing_address_country_iso_code
     */
    public function setBillingAddressCountryIsoCode(string $billing_address_country_iso_code): void
    {
        $this->billing_address_country_iso_code = $billing_address_country_iso_code;
    }

    /**
     * @return \DateTime|null
     */
    public function getApprovedPaymentDate(): ?\DateTime
    {
        return $this->approved_payment_date;
    }

    /**
     * @param \DateTime $approved_payment_date
     */
    public function setApprovedPaymentDate(\DateTime $approved_payment_date): void
    {
        $this->approved_payment_date = $approved_payment_date;
    }

    /**
     * @return string
     */
    public function getLastError(): ?string
    {
        return $this->last_error;
    }

    /**
     * @param string $last_error
     */
    public function setLastError(string $last_error): void
    {
        $this->last_error = $last_error;
    }

    /**
     * @return string
     */
    public function getPaymentGatewayClientToken(): ?string
    {
        return $this->payment_gateway_client_token;
    }

    /**
     * @param string $payment_gateway_client_token
     */
    public function setPaymentGatewayClientToken(string $payment_gateway_client_token): void
    {
        $this->payment_gateway_client_token = $payment_gateway_client_token;
    }

    /**
     * @return string
     */
    public function getPaymentGatewayCartId(): ?string
    {
        return $this->payment_gateway_cart_id;
    }

    /**
     * @param string $payment_gateway_cart_id
     */
    public function setPaymentGatewayCartId(string $payment_gateway_cart_id): void
    {
        $this->payment_gateway_cart_id = $payment_gateway_cart_id;
    }

    /**
     * @return string
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @return \DateTime
     */
    public function getHashCreationDate(): ?\DateTime
    {
        return $this->hash_creation_date;
    }

    /**
     * @return ArrayCollection|SummitAttendeeTicket[]
     */
    public function getTickets()
    {
        return $this->tickets;
    }

    public function isSingleTicket():bool{
        return $this->tickets->count() === 1;
    }

    /**
     * @param SummitAttendeeTicket $ticket
     */
    public function addTicket(SummitAttendeeTicket $ticket)
    {
        if ($this->tickets->contains($ticket)) return;
        $this->tickets->add($ticket);
        $ticket->setOrder($this);
    }

    /**
     * @param int $ticket_id
     * @return SummitAttendeeTicket|null
     */
    public function getTicketById(int $ticket_id): ?SummitAttendeeTicket
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($ticket_id)));
        $ticket = $this->tickets->matching($criteria)->first();
        return $ticket === false ? null : $ticket;
    }

    /**
     * @return SummitOrderExtraQuestionAnswer[]
     */
    public function getExtraQuestionAnswers()
    {
        return $this->extra_question_answers;
    }

    public function clearExtraQuestionAnswers()
    {
        $this->extra_question_answers->clear();
    }

    public function addExtraQuestionAnswer(SummitOrderExtraQuestionAnswer $answer)
    {
        if ($this->extra_question_answers->contains($answer)) return;
        $this->extra_question_answers->add($answer);
        $answer->setOrder($this);
    }

    public function removeExtraQuestionAnswer(SummitOrderExtraQuestionAnswer $answer)
    {
        if (!$this->extra_question_answers->contains($answer)) return;
        $this->extra_question_answers->removeElement($answer);
        $answer->clearOrder();
    }

    use QRGeneratorTrait;

    public function generateQRCode(): string
    {
        $this->qr_code = $this->generateQRFromFields([
            $this->summit->getOrderQRPrefix(),
            $this->number
        ]);

        return $this->qr_code;
    }

    /**
     * @return int
     */
    public function getOwnerId()
    {
        try {
            return is_null($this->owner) ? 0 : $this->owner->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasOwnerCompany(): bool
    {
        return $this->getCompanyId() > 0;
    }

    /**
     * @return int
     */
    public function getCompanyId(): int
    {
        try {
            return is_null($this->owner_company) ? 0 : $this->owner_company->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasOwner(): bool
    {
        return $this->getOwnerId() > 0;
    }

    /**
     * @return float
     */
    public function getRawAmount(): float
    {
        return self::convertToUnit($this->getRawAmountInCents());
    }

    /**
     * @return int
     */
    public function getRawAmountInCents(): int
    {
        $amount_in_cents = 0;
        foreach ($this->tickets as $ticket) {
            $amount_in_cents += $ticket->getRawCostInCents();
        }
        return $amount_in_cents;
    }

    /**
     * @return float
     */
    public function getFinalAmount(): float
    {
        Log::debug(sprintf("SummitOrder::getFinalAmount id %s", $this->id));

        $taxes = [];
        $amount = 0.0;

        foreach ($this->tickets as $ticket) {
            $amount += $ticket->getNetSellingPrice();
            foreach ($ticket->getAppliedTaxes() as $appliedTax)
            {
                $tax = $appliedTax->getTax();
                if(!isset($taxes[$tax->getId()])){
                    $taxes[$tax->getId()] = [
                        'tax' => $tax,
                        'amount' => 0.0
                    ];
                }
                $taxes[$tax->getId()]['amount'] += $appliedTax->getAmount();
            }
        }

        Log::debug(sprintf("SummitOrder::getFinalAmount id %s net amount %s", $this->id, $amount));

        // apply taxes
        foreach ($taxes as $tax_id => $tax_detail){
            $tax_amount = $tax_detail['amount'];
            $tax = $tax_detail['tax'];
            Log::debug(sprintf("SummitOrder::getFinalAmount id %s tax %s tax amount %s", $this->id, $tax->getName(), $tax_amount));
            $tax_amount = $tax->round($tax_amount);
            Log::debug(sprintf("SummitOrder::getFinalAmount id %s tax %s tax amount after rounding %s", $this->id, $tax->getName(), $tax_amount));
            $amount += $tax_amount;
        }

        Log::debug(sprintf("SummitOrder::getFinalAmount id %s amount %s", $this->id, $amount));

        return $amount;
    }

    /**
     * @return int
     */
    public function getFinalAmountInCents(): int
    {
        return self::convertToCents($this->getFinalAmount());
    }

    /**
     * @return float
     */
    public function getFinalAmountAdjusted(): float
    {
        return $this->getFinalAmount() - $this->getRefundedAmount();
    }

    /**
     * @return bool
     */
    public function isFree(): bool
    {
        return $this->getFinalAmountInCents() == 0;
    }

    public function isPrePaid(): bool
    {
        return $this->isPaid() && $this->isOfflineOrder();
    }

    /**
     * @return bool
     */
    public function hasPaymentInfo(): bool
    {
        return !empty($this->payment_gateway_cart_id) || !empty($this->payment_gateway_client_token);
    }

    /**
     * @return float
     */
    public function getTaxesAmount(): float
    {
        $amount = 0.0;
        foreach ($this->getAppliedTaxes() as $tax) {
            $amount += $tax['amount'];
        }
        return $amount;
    }

    /**
     * @return array
     */
    public function getAppliedTaxes(): array
    {

        $applied_taxes = [];

        foreach ($this->tickets as $ticket) {
            foreach ($ticket->getAppliedTaxes() as $appliedTax) {

                $tax = $appliedTax->getTax();

                if (!isset($applied_taxes[$tax->getId()])) {
                    $applied_taxes[$tax->getId()] = [
                        'id' => $tax->getId(),
                        'name' => $tax->getName(),
                        'tax_id' => $tax->getTaxId(),
                        'rate' => $tax->getRate(),
                        'amount_in_cents' => 0,
                        'amount' => 0.00,
                    ];
                }

                $applied_taxes[$tax->getId()]['amount'] = $applied_taxes[$tax->getId()]['amount'] + $appliedTax->getAmount();
            }
        }

        $res = [];
        foreach ($applied_taxes as $tax_id => $applied_tax) {
            $applied_tax['amount_in_cents'] = self::convertToCents($applied_tax['amount']);
            $res[] = $applied_tax;
        }

        return $res;
    }

    /**
     * @return int
     */
    public function getTaxesAmountInCents(): int
    {
        $amount = 0;
        foreach ($this->getAppliedTaxes() as $tax) {
            $amount += $tax['amount_in_cents'];
        }
        return $amount;
    }

    /**
     * @return float
     */
    public function getDiscountAmount(): float
    {
        $amount = 0.0;
        foreach ($this->tickets as $ticket) {
            $amount += $ticket->getDiscount();
        }
        return $amount;
    }

    /**
     * @return float
     */
    public function getDiscountRate(): float
    {
        $discount_amount = $this->getDiscountAmountInCents();
        $raw_amount = $this->getRawAmountInCents();
        if($this->isFree()) return 0.0;
        return $discount_amount > 0 ? ($discount_amount  / $raw_amount ) * 100  : 0.0;
    }

    /**
     * @return int
     */
    public function getDiscountAmountInCents(): int
    {
        return self::convertToCents($this->getDiscountAmount());
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        $ticket = $this->tickets->first();
        if (!$ticket instanceof SummitAttendeeTicket) return 'TBD';
        return $ticket->getCurrency();
    }

    public function getCurrencySymbol(): string
    {
        $ticket = $this->tickets->first();
        if (!$ticket instanceof SummitAttendeeTicket) return 'TBD';
        return $ticket->getCurrencySymbol();
    }

    /**
     * @return string
     */
    public function getOwnerFullName(): string
    {
        $res = "";
        if ($this->hasOwner()) {
            $res = $this->owner->getFullName();
        }
        if (empty($res))
            $res = sprintf("%s %s", $this->owner_first_name, $this->owner_surname);
        return $res;
    }

    /**
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->status == IOrderConstants::PaidStatus;
    }

    public function isReserved(): bool
    {
        return $this->status == IOrderConstants::ReservedStatus;
    }

    public function isConfirmed(): bool{
        return $this->status == IOrderConstants::ConfirmedStatus;
    }

    /**
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status == IOrderConstants::CancelledStatus;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isVoid(): bool
    {
        $ttl_minutes = intval(Config::get("registration.reservation_lifetime", 30));
        $eol = new \DateTime('now', new \DateTimeZone('UTC'));
        $eol->sub(new \DateInterval('PT' . $ttl_minutes . 'M'));
        Log::debug(sprintf("SummitOrder::isVoid status %s created %s eol %s", $this->status, $this->getCreatedUTC()->format('Y-m-d H:i:s'), $eol->format("Y-m-d H:i:s")));
        return (($this->status == IOrderConstants::ErrorStatus || $this->status == IOrderConstants::ReservedStatus) && $this->getCreatedUTC() <= $eol);
    }

    /**
     * @return \DateTime
     */
    public function getLastReminderEmailSentDate(): ?\DateTime
    {
        $last_action_date = $this->last_reminder_email_sent_date;

        if (is_null($last_action_date)) {
            $last_action_date = $this->getCreatedUTC();
        }

        return $last_action_date;
    }

    public function setLastReminderEmailSentDate(?\DateTime $last_reminder_email_sent_date): void{
        $this->last_reminder_email_sent_date = $last_reminder_email_sent_date;
    }

    public function updateLastReminderEmailSentDate(): void
    {
        // This avoids cases where the same object instance (or nearly same timestamp) is mistaken by Doctrine as "unchanged".
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        // force value to differ from any cached clone in memory
        $this->last_reminder_email_sent_date = \DateTime::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-d H:i:s'));
    }


    /**
     * @return bool
     */
    public function isSingleOrder(): bool
    {
        if ($this->tickets->count() > 1) {
            return false;
        }

        $ticket = $this->tickets->first();

        if (!$ticket instanceof SummitAttendeeTicket) return false;

        if ($ticket->getOwnerEmail() != $this->getOwnerEmail()) return false;

        return true;
    }

    /**
     * @return SummitAttendeeTicket|null
     */
    public function getFirstTicket(): ?SummitAttendeeTicket
    {
        if (is_null($this->tickets)) return null;
        if ($this->tickets->count() == 0) return null;
        return $this->tickets->first();
    }

    /*
     * @return string
     */
    public function getExternalId(): ?string
    {
        return $this->external_id;
    }

    /**
     * @param string $external_id
     */
    public function setExternalId(string $external_id): void
    {
        $this->external_id = $external_id;
    }

    /**
     * @return float
     */
    public function getRefundedAmount(): float
    {
        $amount = 0.0;
        foreach ($this->tickets as $ticket) {
            $amount += $ticket->getRefundedAmount();
        }
        return $amount;
    }

    /**
     * @return int
     */
    public function getRefundedAmountInCents(): int
    {
        return self::convertToCents($this->getRefundedAmount());
    }

    /**
     * @return float
     */
    public function getTotalRefundedAmount(): float
    {
        $amount = 0.0;
        foreach ($this->tickets as $ticket) {
            $amount += $ticket->getTotalRefundedAmount();
        }
        return $amount;
    }

    public function getTotalRefundedAmountInCents():int{
        return self::convertToCents($this->getTotalRefundedAmount());
    }

    /**
     * @param SummitAttendee $attendee
     * @return bool
     */
    public function hasTicketOwner(SummitAttendee $attendee):bool{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('owner', $attendee))
            ->andWhere(Criteria::expr()->eq('status', IOrderConstants::PaidStatus));
        return $this->tickets->matching($criteria)->count() > 0;
    }

    /**
     * @return string|null
     */
    public function getCreditCardType():?string
    {
        return $this->credit_card_type;
    }

    /**
     * @return string|null
     */
    public function getCreditCard4Number():?string
    {
        return $this->credit_card_4numbers;
    }
}