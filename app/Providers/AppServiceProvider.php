<?php namespace App\Providers;
/**
 * Copyright 2015 OpenStack Foundation
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

use App\Http\Utils\Logs\LaravelMailerHandler;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use libs\utils\ICacheService;
use models\main\ChatTeamPermission;
use models\main\PushNotificationMessagePriority;
use models\oauth2\IResourceServerContext;
use models\oauth2\ResourceServerContext;
use Sokil\IsoCodes\IsoCodesFactory;

/**
 * Class AppServiceProvider
 * @package App\Providers
 */
class AppServiceProvider extends ServiceProvider
{
    const DefaultSchema = 'https://';
    static $summit_schedule_config_filter_dto_fields = [
        'type',
        'label',
        'is_enabled',
    ];
    static $summit_schedule_config_filter_dto_validation_rules = [
        'label' => 'sometimes|string|max:50',
        'is_enabled' => 'required|boolean',
        'type' => 'required|string|in:DATE,TRACK,TAGS,TRACK_GROUPS,COMPANY,LEVEL,SPEAKERS,VENUES,EVENT_TYPES,TITLE,CUSTOM_ORDER,ABSTRACT',
    ];
    static $summit_schedule_config_pre_filter_dto_fields = [
        'type',
        'values'
    ];
    static $summit_schedule_config_pre_filter_dto_validation_rules = [
        'values' => 'sometimes|int_or_string_array',
        'type' => 'required|string|in:DATE,TRACK,TAGS,TRACK_GROUPS,COMPANY,LEVEL,SPEAKERS,VENUES,EVENT_TYPES,TITLE,CUSTOM_ORDER,ABSTRACT',
    ];
    static $ticket_dto_fields = [
        'id',
        'type_id',
        'promo_code',
        'attendee_first_name',
        'attendee_last_name',
        'attendee_company',
        'attendee_company_id',
        'attendee_email',
        'extra_questions',
        'disclaimer_accepted',
        'share_contact_info',
    ];
    static $ticket_dto_validation_rules = [
        'id' => 'sometimes|int',
        'type_id' => 'sometimes|int',
        'promo_code' => 'nullable|string|max:255',
        'attendee_first_name' => 'nullable|string|max:255',
        'attendee_last_name' => 'nullable|string|max:255',
        'attendee_company' => 'nullable|string|max:255',
        'attendee_company_id' => 'nullable|sometimes|integer',
        'attendee_email' => 'nullable|string|max:255|email',
        'disclaimer_accepted' => 'nullable|boolean',
        'share_contact_info' => 'nullable|boolean',
        'extra_questions' => 'sometimes|extra_question_dto_array'
    ];
    static $extra_question_dto_fields = [
        'question_id',
        'answer',
    ];
    static $extra_question_dto_validation_rules = [
        'question_id' => 'required|int',
        'answer' => 'nullable|string|max:1024',
    ];
    static $event_dto_fields = [
        'id',
        'title',
        'start_date',
        'end_date',
        'type_id',
        'track_id',
        'location_id',
        'description',
        'rsvp_link',
        'head_count',
        'social_description',
        'allow_feedback',
        'tags',
        'sponsors',
        'attendees_expected_learnt',
        'level',
        'feature_cloud',
        'to_record',
        'speakers',
        'moderator_speaker_id',
        'groups',
        'selection_plan_id',
        'duration',
        'streaming_url',
        'streaming_type',
        'meeting_url',
        'etherpad_link',
    ];

    static $event_dto_fields_publish = [
        'id',
        'start_date',
        'end_date',
        'duration',
        'location_id',
    ];

    static $event_dto_publish_validation_rules = [
        'id' => 'required|integer',
        'location_id' => 'sometimes|required|integer',
        'start_date' => 'sometimes|required|date_format:U',
        'duration' => 'sometimes|integer|min:0',
        'end_date' => 'sometimes|required_with:start_date|date_format:U|after:start_date',
    ];

    static $event_dto_validation_rules = [
        // summit event rules
        'id' => 'required|integer',
        'title' => 'sometimes|string|max:255',
        'description' => 'sometimes|string|max:1100',
        'rsvp_link' => 'sometimes|url',
        'head_count' => 'sometimes|integer',
        'social_description' => 'sometimes|string|max:100',
        'location_id' => 'sometimes|integer',
        'start_date' => 'sometimes|date_format:U',
        'end_date' => 'sometimes|required_with:start_date|date_format:U|after:start_date',
        'allow_feedback' => 'sometimes|boolean',
        'type_id' => 'sometimes|required|integer',
        'track_id' => 'sometimes|required|integer',
        'tags' => 'sometimes|string_array',
        'sponsors' => 'sometimes|int_array',
        // presentation rules
        'attendees_expected_learnt' => 'sometimes|string|max:1100',
        'feature_cloud' => 'sometimes|boolean',
        'to_record' => 'sometimes|boolean',
        'speakers' => 'sometimes|int_array',
        'moderator_speaker_id' => 'sometimes|integer',
        // group event
        'groups' => 'sometimes|int_array',
        'selection_plan_id' => 'sometimes|integer',
        'duration' => 'sometimes|integer|min:0',
        'streaming_url' => 'nullable|sometimes|url',
        'streaming_type' => 'required_with:streaming_url|string|in:VOD,LIVE',
        'etherpad_link' => 'nullable|sometimes|url',
        'meeting_url' => 'nullable|sometimes|url',
    ];


    static $rsvp_answer_dto_fields = [
        'question_id',
        'value',
    ];

    static $rsvp_answer_validation_rules = [
        'question_id' => 'required|integer',
        'value' => 'sometimes|string_or_string_array',
    ];

    /**
     * Bootstrap any application services.
     * @return void
     */
    public function boot()
    {
        URL::forceScheme('https');

        $logger = Log::getLogger();
        foreach ($logger->getHandlers() as $handler) {
            $handler->setLevel(Config::get('log.level', 'debug'));
        }

        //set email log
        $to = Config::get('log.to_email', '');
        $from = Config::get('log.from_email', '');

        if (!empty($to) && !empty($from)) {
            $subject = Config::get('log.email_subject', 'openstackid-resource-server error');
            $cacheService = App::make(ICacheService::class);
            $handler = new LaravelMailerHandler($cacheService, $to, $subject, $from);
            $handler->setLevel(Config::get('log.email_level', 'error'));
            $logger->pushHandler($handler);
        }

        Validator::extend('int_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('int_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be an array of integers", $attribute);
            });
            if (!is_array($value)) return false;
            foreach ($value as $element) {
                if (!is_numeric($element)) return false;
            }
            return true;
        });

        // @see  RFC 5322 - https://www.rfc-editor.org/rfc/rfc5322#section-3.4
        Validator::extend('emails_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('emails_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be an array of valid emails", $attribute);
            });
            if (!is_array($value)) return false;
            foreach ($value as $element) {
                if (!is_string($element)) return false;
                if(!filter_var($element, FILTER_VALIDATE_EMAIL)) return false;
            }
            return true;
        });

        Validator::extend('int_or_string_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('int_or_string_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be an array of integers or strings", $attribute);
            });
            if (!is_array($value)) return false;
            foreach ($value as $element) {
                if (!is_numeric($element) && !is_string($element)) return false;
            }
            return true;
        });

        Validator::extend('event_dto_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('event_dto_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf
                (
                    "%s should be an array of event data {id : int, location_id: int, start_date: int (epoch), end_date: int (epoch)}",
                    $attribute);
            });
            if (!is_array($value)){
                Log::debug(sprintf("event_dto_array::is not array %s", print_r($value, true)));
                return false;
            }

            foreach ($value as $element) {
                foreach ($element as $key => $element_val) {
                    if (!in_array($key, self::$event_dto_fields)) {
                        Log::debug(sprintf("event_dto_array::invalid key %s", $key));
                        return false;
                    }
                }

                // Creates a Validator instance and validates the data.
                $validation = Validator::make($element, self::$event_dto_validation_rules);

                if ($validation->fails()) {
                    Log::debug(sprintf("event_dto_array::validation fails %s", print_r($validation->errors(), true)));
                    return false;
                }
            }
            return true;
        });

        Validator::extend('extra_question_dto_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('extra_question_dto_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf
                (
                    "%s should be an array of extra question data {question_id : int, answer: string}",
                    $attribute);
            });

            if (!is_array($value)) return false;

            foreach ($value as $element) {
                foreach ($element as $key => $element_val) {
                    if (!in_array($key, self::$extra_question_dto_fields)) return false;
                }

                // Creates a Validator instance and validates the data.
                $validation = Validator::make($element, self::$extra_question_dto_validation_rules);

                if ($validation->fails()) return false;
            }
            return true;
        });

        Validator::extend('summit_schedule_config_filter_dto_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('summit_schedule_config_filter_dto_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf
                (
                    "%s should be an array of schedule config filter data",
                    $attribute
                );
            });
            if (!is_array($value)) return false;
            foreach ($value as $element) {
                foreach ($element as $key => $element_val) {
                    if (!in_array($key, self::$summit_schedule_config_filter_dto_fields)) return false;
                }

                // Creates a Validator instance and validates the data.
                $validation = Validator::make($element, self::$summit_schedule_config_filter_dto_validation_rules);

                if ($validation->fails()) return false;
            }
            return true;
        });

        Validator::extend('summit_schedule_config_pre_filter_dto_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('summit_schedule_config_pre_filter_dto_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf
                (
                    "%s should be an array of schedule config pre filter data",
                    $attribute
                );
            });
            if (!is_array($value)) return false;
            foreach ($value as $element) {
                foreach ($element as $key => $element_val) {
                    if (!in_array($key, self::$summit_schedule_config_pre_filter_dto_fields)) return false;
                }

                // Creates a Validator instance and validates the data.
                $validation = Validator::make($element, self::$summit_schedule_config_pre_filter_dto_validation_rules);

                if ($validation->fails()) return false;
            }
            return true;
        });

        Validator::extend('ticket_dto_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('ticket_dto_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf
                (
                    "%s should be an array of ticket data {id: int|optional, type_id: int|optional, promo_code:string|optional, attendee_first_name:string|optional, attendee_last_name:string|optional, attendee_email:string|optional, extra_questions:array|optional }",
                    $attribute
                );
            });
            if (!is_array($value)) return false;
            foreach ($value as $element) {
                foreach ($element as $key => $element_val) {
                    if (!in_array($key, self::$ticket_dto_fields)) return false;
                }

                // Creates a Validator instance and validates the data.
                $validation = Validator::make($element, self::$ticket_dto_validation_rules);

                if ($validation->fails()) return false;
            }
            return true;
        });

        Validator::extend('event_dto_publish_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('event_dto_publish_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf
                (
                    "%s should be an array of event data {id : int, location_id: int, start_date: int (epoch), end_date: int (epoch)}",
                    $attribute);
            });
            if (!is_array($value)) return false;
            foreach ($value as $element) {
                foreach ($element as $key => $element_val) {
                    if (!in_array($key, self::$event_dto_fields_publish)) return false;
                }

                // Creates a Validator instance and validates the data.
                $validation = Validator::make($element, self::$event_dto_publish_validation_rules);

                if ($validation->fails()) return false;
            }
            return true;
        });

        Validator::extend('text', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('text', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be a valid text", $attribute);
            });

            return preg_match('/^[^<>\"\']+$/u', $value);
        });

        Validator::extend('string_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('string_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be an array of strings", $attribute);
            });
            if (!is_array($value))
                return false;
            foreach ($value as $element) {
                if (!is_string($element))
                    return false;
            }
            return true;
        });

        Validator::extend('string_or_string_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('string_or_string_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be a string or an array of strings", $attribute);
            });

            if (is_string($value))
                return true;

            if (!is_array($value))
                return false;

            foreach ($value as $element) {
                if (!is_string($element))
                    return false;
            }
            return true;
        });

        Validator::extend('url_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('url_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should a list of valid urls.", $attribute);
            });
            if (!is_array($value)) return false;
            foreach ($value as $element) {
                if (!filter_var($element, FILTER_VALIDATE_URL)) {
                    // try to add the default schema in front of the url to valiate
                    $element = self::DefaultSchema . $element;
                    if (!filter_var($element, FILTER_VALIDATE_URL))
                        return false;
                }
            }
            return true;
        });

        Validator::extend('entity_value_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('entity_value_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be an array of {id,value} tuple", $attribute);
            });
            if (!is_array($value)) return false;
            foreach ($value as $element) {
                if (!isset($element['id'])) return false;
                if (!isset($element['value'])) return false;
            }
            return true;
        });

        Validator::extend('link_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('link_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be an array of {title,link} tuple", $attribute);
            });

            if (!is_array($value)) return false;
            foreach ($value as $element) {
                // Creates a Validator instance and validates the data.
                $validation = Validator::make($element, [
                    'title' => 'required|string|max:255',
                    'link' => 'required|url',
                ]);

                if ($validation->fails()) return false;
            }
            return true;
        });

        Validator::extend('team_permission', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('team_permission', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be a valid permission value (ADMIN, WRITE, READ)", $attribute);
            });
            return in_array($value, [ChatTeamPermission::Read, ChatTeamPermission::Write, ChatTeamPermission::Admin]);
        });

        Validator::extend('chat_message_priority', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('chat_message_priority', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be a valid message priority value (NORMAL, HIGH)", $attribute);
            });
            return in_array($value, [PushNotificationMessagePriority::Normal, PushNotificationMessagePriority::High]);
        });

        Validator::extend('after_or_null_epoch', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('after_or_null_epoch', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be zero or after %s", $attribute, $parameters[0]);
            });
            $data = $validator->getData();
            if (is_null($value) || intval($value) == 0) return true;
            if (isset($data[$parameters[0]])) {
                $compare_to = $data[$parameters[0]];
                $parsed = date_parse_from_format('U', $value);
                $valid = $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
                return $valid && intval($compare_to) < intval($value);
            }
            return true;
        });

        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('greater_than_field', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be greather than %s", $attribute, $parameters[0]);
            });
            $data = $validator->getData();
            if (is_null($value) || intval($value) == 0) return true;
            if (isset($data[$parameters[0]])) {
                $compare_to = $data[$parameters[0]];
                return intval($compare_to) < intval($value);
            }
            return true;
        });

        Validator::extend('valid_epoch', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('valid_epoch', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be a valid epoch value", $attribute);
            });
            return intval($value) > 0;
        });

        Validator::extend('hex_color', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('hex_color', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be a valid hex color value", $attribute);
            });
            if (strlen($value) != 6) return false;
            if (!ctype_xdigit($value)) return false;
            return true;
        });

        Validator::extend('geo_latitude', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('geo_latitude', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be a valid coordinate value  (-90.00,+90.00)", $attribute);
            });

            $value = floatval($value);
            return !($value < -90.00 || $value > 90.00);

        });

        Validator::extend('geo_longitude', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('geo_longitude', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be a valid coordinate value (-180.00,+180.00)", $attribute);
            });

            $value = floatval($value);
            return !($value < -180.00 || $value > 180.00);
        });

        Validator::extend('country_iso_alpha2_code', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('country_iso_alpha2_code', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be a valid country iso code", $attribute);
            });
            if (!is_string($value)) return false;
            $value = trim($value);

            $isoCodes = new IsoCodesFactory();
            $countries = $isoCodes->getCountries();
            $country = $countries->getByAlpha2($value);

            return !is_null($country);
        });

        Validator::extend('currency_iso', function ($attribute, $value, $parameters, $validator) {

            $validator->addReplacer('currency_iso', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be a valid currency iso 4217 code", $attribute);
            });
            if (!is_string($value)) return false;
            $value = trim($value);

            $isoCodes = new IsoCodesFactory();

            $currencies = $isoCodes->getCurrencies();

            $currency = $currencies->getByLetterCode($value);

            return !is_null($currency);

        });

        Validator::extend('greater_than', function ($attribute, $value, $otherValue) {
            return intval($value) > intval($otherValue[0]);
        });

        Validator::extend('greater_than_or_equal', function ($attribute, $value, $otherValue) {
            return intval($value) >= intval($otherValue[0]);
        });

        Validator::extend('rsvp_answer_dto_array', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('rsvp_answer_dto_array', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf
                (
                    "%s should be an array of rsvp answer data {question_id : int, value: string, values: string array}",
                    $attribute);
            });
            if (!is_array($value)) return false;
            foreach ($value as $element) {
                foreach ($element as $key => $element_val) {
                    if (!in_array($key, self::$rsvp_answer_dto_fields))
                        return false;
                }

                // Creates a Validator instance and validates the data.
                $validation = Validator::make($element, self::$rsvp_answer_validation_rules);

                if ($validation->fails())
                    return false;
            }
            return true;
        });

        Validator::extend('megabyte_aligned', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('megabyte_aligned', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be aligned to 1024 KB", $attribute);
            });

            $value = intval($value);

            if ($value <= 0) return false;

            return ($value % 1024 == 0);
        });

        Validator::extend('single_word', function ($attribute, $value, $parameters, $validator) {
            $validator->addReplacer('single_word', function ($message, $attribute, $rule, $parameters) use ($validator) {
                return sprintf("%s should be single word", $attribute);
            });

            return is_string($value) && !preg_match('/\s/u', $value);
        });
    }

    /**
     * Register any application services.
     * @return void
     */
    public function register()
    {

        App::singleton(IResourceServerContext::class, ResourceServerContext::class);

        App::bind('resource_server_context', function ($app) {
            // bind facade accesor with singleton instance
            return $app->make(IResourceServerContext::class);
        });

        App::singleton('App\Models\ResourceServer\IAccessTokenService', 'App\Models\ResourceServer\AccessTokenService');
        App::singleton('App\Models\ResourceServer\IApi', 'models\\resource_server\\Api');
        App::singleton('App\Models\ResourceServer\IApiEndpoint', 'models\\resource_server\\ApiEndpoint');
        App::singleton('App\Models\ResourceServer\IApiScope', 'models\\resource_server\\ApiScope');
    }
}
