<?php namespace App\Rules;
/*
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

use Illuminate\Contracts\Validation\Rule;

/**
 * Class AllowedEmailDomainsArray
 * @package App\Rules
 *
 * Validates that the value is an array of allowed email domain patterns.
 * Each entry must match one of:
 *   - @domain.com  (exact domain match, starts with @)
 *   - .tld         (TLD/suffix match, starts with .)
 *   - user@example.com (exact email match)
 */
final class AllowedEmailDomainsArray implements Rule
{
    /**
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (!is_array($value))
            return false;

        foreach ($value as $element) {
            if (!is_string($element) || empty(trim($element)))
                return false;

            $element = trim($element);

            // @domain.com — must have at least one char after @
            if (str_starts_with($element, '@')) {
                if (!preg_match('/^@[\w][\w.-]+$/', $element))
                    return false;
            }
            // .tld — one or more dot-prefixed alphanumeric labels
            // (accepts .edu, .co.uk, .com.au, .ac.uk; rejects ., ..com, .com., .co..uk)
            elseif (str_starts_with($element, '.')) {
                if (!preg_match('/^\.[a-z0-9]+(?:\.[a-z0-9]+)*$/i', $element))
                    return false;
            }
            // user@example.com — standard email-like pattern
            elseif (str_contains($element, '@')) {
                if (!preg_match('/^[^@\s]+@[\w][\w.-]+$/', $element))
                    return false;
            }
            else {
                return false;
            }
        }
        return true;
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return trans('The :attribute must be an array of valid email domain patterns (@domain.com, .tld, or user@example.com).');
    }
}
