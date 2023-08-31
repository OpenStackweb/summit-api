<?php namespace Libs\ModelSerializers;
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

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use libs\utils\JsonUtils;
use models\oauth2\IResourceServerContext;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use ReflectionClass;
use Exception;

/**
 * Class AbstractSerializer
 * @package Libs\ModelSerializers
 */
abstract class AbstractSerializer implements IModelSerializer
{
    const MaxCollectionPage = 10;
    /**
     * @var IEntity
     */
    protected $object;

    /**
     * @var IResourceServerContext
     */
    protected $resource_server_context;

    /**
     * AbstractSerializer constructor.
     * @param $object
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct($object, IResourceServerContext $resource_server_context)
    {
        $this->object = $object;
        $this->resource_server_context = $resource_server_context;
    }

    protected static $array_mappings = [
        'Id' => 'id:json_int',
    ];

    protected static $allowed_fields = [];

    protected static $allowed_relations = [];

    protected static $expand_mappings = [];

    /**
     * @return array
     */
    protected function getAllowedFields():array
    {
        try {
            $allowed_fields = [];
            $hierarchy = $this->getClassHierarchy();
            $cur_class = get_class($this);
            $cache_key = $cur_class . '.allowed_fields';

            if (Cache::has($cache_key)) {
                $res = Cache::get($cache_key);
                if(!empty($res))
                    return json_decode($res, true);
            }

            foreach ($hierarchy as $class_name) {
                if ($class_name === AbstractSerializer::class) continue;
                $refClass = new ReflectionClass($class_name);
                if ($refClass->hasProperty("allowed_fields")) {
                    $prop = $refClass->getProperty("allowed_fields");
                    $prop->setAccessible(true);
                    $allowed_fields = array_merge($allowed_fields, $prop->getValue());
                }
            }

            $allowed_fields = array_merge($allowed_fields, static::$allowed_fields);
            Cache::put($cache_key, json_encode($allowed_fields));
            return $allowed_fields;
        }
        catch (Exception $ex){
            Log::error($ex);
            return [];
        }
    }

    /**
     * @return array
     */
    protected function getExpandsMappings():array
    {
        try {
            $expands = [];
            $hierarchy = $this->getClassHierarchy();
            $cur_class = get_class($this);
            $cache_key = $cur_class . '.expand_mappings';

            if (Cache::has($cache_key)) {
                $res = Cache::get($cache_key);
                if(!empty($res))
                    return json_decode($res, true);
            }

            foreach ($hierarchy as $class_name) {
                if ($class_name === AbstractSerializer::class) continue;
                $refClass = new ReflectionClass($class_name);
                if ($refClass->hasProperty("expand_mappings")) {
                    $prop = $refClass->getProperty("expand_mappings");
                    $prop->setAccessible(true);
                    $expands = array_merge($expands, $prop->getValue());
                }
            }
            $expands = array_merge($expands, static::$expand_mappings);
            Cache::put($cache_key, json_encode($expands));
            return $expands;
        }
        catch (Exception $ex){
            Log::error($ex);
            return [];
        }
    }

    /**
     * @return array
     */
    protected function getAllowedRelations():array
    {
        try {
            $relations = [];
            $hierarchy = $this->getClassHierarchy();
            $cur_class = get_class($this);
            $cache_key = $cur_class . '.relations';

            if (Cache::has($cache_key)) {
                $res = Cache::get($cache_key);
                if(!empty($res))
                    return json_decode($res, true);
            }

            foreach ($hierarchy as $class_name) {
                if ($class_name === AbstractSerializer::class) continue;
                $refClass = new ReflectionClass($class_name);
                if ($refClass->hasProperty("allowed_relations")) {
                    $prop = $refClass->getProperty("allowed_relations");
                    $prop->setAccessible(true);
                    $relations = array_merge($relations, $prop->getValue());
                }
            }

            $relations = array_merge($relations, static::$allowed_relations);
            Cache::put($cache_key, json_encode($relations));
            return $relations;
        }
        catch (Exception $ex){
            Log::error($ex);
            return [];
        }
    }

    /**
     * @return array
     */
    private function getAttributeMappings():array
    {
        try {
            $mappings = [];
            $hierarchy = $this->getClassHierarchy();
            $cur_class = get_class($this);
            $cache_key = $cur_class . '.mappings';

            if (Cache::has($cache_key)) {
                $res = Cache::get($cache_key);
                if(!empty($res))
                    return json_decode($res, true);
            }

            foreach ($hierarchy as $class_name) {
                $refClass = new ReflectionClass($class_name);
                if ($refClass->hasProperty("array_mappings")) {
                    $prop = $refClass->getProperty("array_mappings");
                    $prop->setAccessible(true);
                    $mappings = array_merge($mappings, $prop->getValue());
                }
            }

            $mappings = array_merge($mappings, static::$array_mappings);
            Cache::put($cache_key, json_encode($mappings));

            return $mappings;
        }
        catch (Exception $ex){
            Log::error($ex);
            return [];
        }
    }

    /**
     * @return array
     */
    private function getClassHierarchy():array
    {
        return array_reverse($this->get_class_lineage($this));
    }

    /**
     * @param $object
     * @return array
     */
    private function get_class_lineage($object):array
    {
        $class_name = get_class($object);
        $parents = array_values(class_parents($class_name));
        return array_merge(array($class_name), $parents);
    }

    const BoolType = 'json_boolean';
    const EpochType = 'datetime_epoch';
    const StringType = 'json_string';
    const IntType = 'json_int';
    const FloatType = 'json_float';
    const ObfuscatedEmailType = 'json_obfuscated_email';
    const UrlType = 'json_url';
    const ColorType = 'json_color';
    const JsonStringArray = 'json_string_array';

    const ValidTypes = [
        self::BoolType,
        self::EpochType,
        self::StringType,
        self::IntType,
        self::FloatType,
        self::ObfuscatedEmailType,
        self::UrlType,
        self::ColorType,
        self::JsonStringArray,
    ];

    /**
     * @param string $field
     * @param string $type
     * @return string
     */
    public static function buildMapping(string $field, string $type): string
    {
        if (!in_array($type, self::ValidTypes))
            throw new \InvalidArgumentException();
        return sprintf("%s:%s", $field, $type);
    }

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $values = [];
        $method_prefix = ['get', 'is'];
        if (!count($fields)) $fields = $this->getAllowedFields();
        $mappings = $this->getAttributeMappings();
        if (count($mappings)) {
            $new_values = [];
            $first_level_fields = array_filter($fields, function($elem) {
                return !str_contains(trim($elem), ".");
            });
            foreach ($mappings as $attribute => $mapping) {
                $mapping = preg_split('/:/', $mapping);
                if (count($first_level_fields) > 0 && !in_array($mapping[0], $first_level_fields)) continue;
                $value = null;
                $method_found = false;
                foreach ($method_prefix as $prefix) {
                    if (method_exists($this->object, $prefix . $attribute)) {
                        try {
                            $value = call_user_func([$this->object, $prefix . $attribute]);
                            $method_found = true;
                            break;
                        } catch (\Exception $ex) {
                            Log::warning($ex);
                            $value = null;
                        }
                    }
                }

                if (!$method_found) {
                    try {
                        //try dynamic one
                        $value = call_user_func([$this->object, 'get' . $attribute]);
                    } catch (\Exception $ex) {
                        Log::warning($ex);
                        $value = null;
                    }
                }

                if (count($mapping) > 1) {
                    //we have a formatter ...
                    switch (strtolower($mapping[1])) {
                        case 'datetime_epoch':
                            {
                                if (!is_null($value)) {
                                    $value = $value->getTimestamp();
                                }
                            }
                            break;
                        case 'json_string':
                            {
                                $value = JsonUtils::toJsonString($value);
                            }
                            break;
                        case 'json_string_array':
                            {
                                if(is_array($value))
                                    $value = array_map(function($a){return JsonUtils::toJsonString($a);}, $value);
                            }
                            break;
                        case 'json_boolean':
                            {
                                $value = JsonUtils::toJsonBoolean($value);
                            }
                            break;
                        case 'json_color':
                            {
                                $value = JsonUtils::toJsonColor($value);
                            }
                            break;
                        case 'json_int':
                            {
                                $value = JsonUtils::toJsonInt($value);
                            }
                            break;
                        case 'json_float':
                            {
                                $value = JsonUtils::toJsonFloat($value);
                            }
                            break;
                        case 'json_money':
                            {
                                $value = JsonUtils::toJsonMoney($value);
                            }
                            break;
                        case 'json_obfuscated_email':
                        {
                            $value = JsonUtils::toObfuscatedEmail($value);
                        }
                        case 'json_null_email':
                        {
                            $value = JsonUtils::toNullEmail($value);
                        }
                        case 'json_url':
                            {
                                $value = JsonUtils::encodeUrl($value);
                            }
                            break;
                    }
                }
                $new_values[$mapping[0]] = $value;
            }
            $values = $new_values;
        }

        return $this->_expand($values, $expand, $fields, $relations, $params);
    }

    /**
     * @param array $values
     * @param string|null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    protected function _expand(array $values, ?string $expand, array $fields = [], array $relations = [], array  $params = []): array
    {
        $mappings = $this->getExpandsMappings();

        if (!empty($expand) && count($mappings) > 0) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                $relation = ltrim(trim($relation),'*');
                $serializerSpec = $mappings[$relation] ?? null;
                if (is_null($serializerSpec)) continue;
                $serializerClass = $serializerSpec['type'] ?? null;
                if(empty($serializerClass)) continue;
                $original_attribute = $serializerSpec['original_attribute'] ?? $relation;
                $attribute = $serializerSpec['attribute'] ?? $relation;
                $getter = $serializerSpec['getter'] ?? null;
                if(empty($getter)) continue;
                $has = $serializerSpec['has'] ?? null;
                $test_rule = $serializerSpec['test_rule'] ?? null;
                $should_skip_rule = $serializerSpec['should_skip_rule'] ?? null;
                $serializer_type = $serializerSpec['serializer_type'] ?? SerializerRegistry::SerializerType_Public;
                $serializer = new $serializerClass($original_attribute, $attribute, $getter, $has, $serializer_type, $test_rule, $should_skip_rule);
                $values = $serializer->serialize($this->object, $values, $expand, $fields, $relations, $params);
            }
        }

        return $values;
    }

    /**
     * @param string|null $expand_str
     * @param string $prefix
     * @return string
     */
    public static function filterExpandByPrefix(?string $expand_str, string $prefix):?string
    {
        if(empty($expand_str)) return '';
        $expand_to = explode(',', $expand_str);
        $filtered_expand = array_filter($expand_to, function ($element) use ($prefix) {
            if(str_starts_with($element, '*')) return true;
            return preg_match('/^' . preg_quote($prefix, '/') . '\./', strtolower(trim($element))) > 0;
        });
        $res = '';
        foreach ($filtered_expand as $filtered_expand_elem) {
            if (strlen($res) > 0) $res .= ',';
            $res .= str_replace_first($prefix . ".", "", strtolower(trim($filtered_expand_elem)));
        }
        return $res;
    }

    /**
     * @param array $fields
     * @param string $prefix
     * @return array
     */
    public static function filterFieldsByPrefix(array $fields, string $prefix):array{
        if(!count($fields)) return [];
        $filtered_fields = array_filter($fields, function ($element) use ($prefix) {
            return preg_match('/^' . preg_quote($prefix, '/') . '\./', strtolower(trim($element))) > 0;
        });
        $res = [];
        foreach ($filtered_fields as $filtered_field_elem) {
            $res[] = str_replace_first($prefix . ".", "", strtolower(trim($filtered_field_elem)));
        }
        return $res;
    }

    /**
     * @param string|null $prefix
     * @param string $expand
     * @return string
     */
    protected static function getExpandForPrefix(?string $prefix, string $expand): string
    {

        if(empty($prefix)) return '';
        $prefix_expand = [];
        foreach (explode(',', $expand) as $e) {
            if (strstr($e, $prefix . ".") !== false)
                $prefix_expand[] = str_replace($prefix . ".", "", $e);
        }

        return implode(',', $prefix_expand);
    }
}