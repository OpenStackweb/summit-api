<?php namespace utils;
/**
 * Copyright 2018 OpenStack Foundation
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

use App\Http\Utils\Filters\IQueryApplyable;
use Doctrine\ORM\QueryBuilder;

/**
 * Class DoctrineInstanceOfFilterMapping
 * @package utils
 */
final class DoctrineInstanceOfFilterMapping extends FilterMapping implements IQueryApplyable
{

    private $class_names = [];

    public function __construct($alias, $class_names = [])
    {
        $this->class_names = $class_names;
        parent::__construct($alias, sprintf("%s %s :class_name", $alias, self::InstanceOfDoctrine));
    }

    /**
     * @param FilterElement $filter
     * @throws \Exception
     */
    public function toRawSQL(FilterElement $filter)
    {
        throw new \Exception;
    }

    const InstanceOfDoctrine = 'INSTANCE OF';

    private function translateClassName($value)
    {
        if (isset($this->class_names[$value])) return $this->class_names[$value];
        return $value;
    }

    private function buildWhere(QueryBuilder $query, FilterElement $filter):string{
        $value = $filter->getValue();

        if (is_array($value)) {
            $where_components = [];
            // see @https://github.com/doctrine/orm/issues/4462
            foreach ($value as $val) {
                $where_components[] =  str_replace(":class_name", $this->translateClassName($val), $this->where);
            }
            return implode(sprintf(" %s ", $filter->getSameFieldOp()), $where_components);
        }
        return str_replace(":class_name", $this->translateClassName($filter->getValue()), $this->where);
    }

    /**
     * @param QueryBuilder $query
     * @param FilterElement $filter
     * @return QueryBuilder
     */
    public function apply(QueryBuilder $query, FilterElement $filter): QueryBuilder
    {
        return $query->andWhere($this->buildWhere($query, $filter));
    }

    /**
     * @param QueryBuilder $query
     * @param FilterElement $filter
     * @return string
     */
    public function applyOr(QueryBuilder $query, FilterElement $filter): string
    {
        return $this->buildWhere($query, $filter);
    }

}