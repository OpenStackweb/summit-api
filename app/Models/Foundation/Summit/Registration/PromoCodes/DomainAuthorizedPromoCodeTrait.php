<?php namespace models\summit;
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

use models\exceptions\ValidationException;
use Doctrine\ORM\Mapping AS ORM;

/**
 * Trait DomainAuthorizedPromoCodeTrait
 * @package models\summit
 */
trait DomainAuthorizedPromoCodeTrait
{
    /**
     * @var array
     */
    #[ORM\Column(name: 'AllowedEmailDomains', type: 'json', nullable: true)]
    protected $allowed_email_domains = [];

    /**
     * @var int
     */
    #[ORM\Column(name: 'QuantityPerAccount', type: 'integer')]
    protected $quantity_per_account = 0;

    /**
     * @return array
     */
    public function getAllowedEmailDomains(): array
    {
        return $this->allowed_email_domains ?? [];
    }

    /**
     * @param array $allowed_email_domains
     */
    public function setAllowedEmailDomains(array $allowed_email_domains): void
    {
        $this->allowed_email_domains = $allowed_email_domains;
    }

    /**
     * @return int
     */
    public function getQuantityPerAccount(): int
    {
        return $this->quantity_per_account;
    }

    /**
     * @param int $quantity_per_account
     */
    public function setQuantityPerAccount(int $quantity_per_account): void
    {
        $this->quantity_per_account = $quantity_per_account;
    }

    /**
     * Check if the given email matches any pattern in allowed_email_domains.
     * Pattern types (case-insensitive):
     *   - @domain.com  → exact domain match
     *   - .tld         → suffix match (TLD/subdomain)
     *   - user@example.com → exact email match
     * Empty array means no restriction (passes all).
     *
     * @param string $email
     * @return bool
     */
    public function matchesEmailDomain(string $email): bool
    {
        $domains = $this->getAllowedEmailDomains();
        if (empty($domains)) return true;

        $email = strtolower(trim($email));
        if (empty($email)) return false;

        $atPos = strpos($email, '@');
        if ($atPos === false) return false;

        $emailDomain = substr($email, $atPos);

        foreach ($domains as $pattern) {
            $pattern = strtolower(trim($pattern));
            if (empty($pattern)) continue;

            // Pattern starts with @ → exact domain match (e.g., @acme.com)
            if (str_starts_with($pattern, '@')) {
                if ($emailDomain === $pattern) return true;
            }
            // Pattern starts with . → suffix match (e.g., .edu, .gov)
            elseif (str_starts_with($pattern, '.')) {
                if (str_ends_with($emailDomain, $pattern)) return true;
            }
            // Pattern contains @ → exact email match (e.g., user@example.com)
            elseif (str_contains($pattern, '@')) {
                if ($email === $pattern) return true;
            }
        }

        return false;
    }

    /**
     * Validates email against allowed_email_domains.
     * Throws ValidationException if no match.
     *
     * @param string $email
     * @param null|string $company
     * @return bool
     * @throws ValidationException
     */
    public function checkSubject(string $email, ?string $company): bool
    {
        if (!$this->matchesEmailDomain($email)) {
            throw new ValidationException(
                sprintf(
                    "Email %s does not match any allowed email domain for promo code %s.",
                    $email,
                    $this->getCode()
                )
            );
        }
        return true;
    }
}
