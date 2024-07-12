<?php namespace ModelSerializers;
/**
 * Copyright 2016 OpenStack Foundation
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
use App\ModelSerializers\PushNotificationMessageSerializer;
use Libs\ModelSerializers\AbstractSerializer;
use models\main\Member;
use models\summit\SummitPushNotification;
use models\summit\SummitPushNotificationChannel;

/**
 * Class SummitPushNotificationSerializer
 * @package ModelSerializers
 */
final class SummitPushNotificationSerializer extends PushNotificationMessageSerializer {
  protected static $array_mappings = [
    "Channel" => "channel:json_string",
    "SummitId" => "summit_id:json_int",
  ];

  /**
   * @param null $expand
   * @param array $fields
   * @param array $relations
   * @param array $params
   * @return array
   */
  public function serialize(
    $expand = null,
    array $fields = [],
    array $relations = [],
    array $params = [],
  ) {
    $notification = $this->object;
    if (!$notification instanceof SummitPushNotification) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);

    if ($notification->getChannel() == SummitPushNotificationChannel::Event) {
      $values["event_id"] = $notification->getSummitEvent()->getId();
    }

    if ($notification->getChannel() == SummitPushNotificationChannel::Group) {
      $values["group_id"] = $notification->getGroup()->getId();
    }

    if ($notification->getChannel() == SummitPushNotificationChannel::Members) {
      $values["recipients"] = [];
      foreach ($notification->getRecipients() as $recipient) {
        if (!$recipient instanceof Member) {
          continue;
        }
        $values["recipients"][] = $recipient->getId();
      }
    }

    if (!empty($expand)) {
      foreach (explode(",", $expand) as $relation) {
        $relation = trim($relation);
        switch ($relation) {
          case "event":
            if ($notification->getChannel() != SummitPushNotificationChannel::Event) {
              continue;
            }
            unset($values["event_id"]);
            $values["event"] = SerializerRegistry::getInstance()
              ->getSerializer($notification->getSummitEvent())
              ->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
            break;
          case "group":
            if ($notification->getChannel() != SummitPushNotificationChannel::Group) {
              continue;
            }
            unset($values["group_id"]);
            $values["group"] = SerializerRegistry::getInstance()
              ->getSerializer($notification->getGroup())
              ->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
            break;
          case "recipients":
            if ($notification->getChannel() != SummitPushNotificationChannel::Members) {
              continue;
            }
            $values["recipients"] = [];
            foreach ($notification->getRecipients() as $recipient) {
              $values["recipients"][] = SerializerRegistry::getInstance()
                ->getSerializer($recipient)
                ->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
            }
            break;
        }
      }
    }

    return $values;
  }
}
