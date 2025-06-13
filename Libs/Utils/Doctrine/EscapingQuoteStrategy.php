<?php namespace Libs\Utils\Doctrine;
/**
 * Copyright 2021 OpenStack Foundation
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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\JoinColumnMapping;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;

/**
 * A set of rules for determining the physical column, alias and table quotes and automatically escape database reserved
 * keyword.
 *
 */
class EscapingQuoteStrategy implements QuoteStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getColumnName($fieldName, ClassMetadata $class, AbstractPlatform $platform): string
    {
        if (isset($class->fieldMappings[$fieldName]['quoted'])) {
            return $platform->quoteIdentifier($class->fieldMappings[$fieldName]['columnName']);
        }
        $reservedKeyList = $platform->getReservedKeywordsList();
        if ($reservedKeyList->isKeyword($fieldName)) {
            return $platform->quoteIdentifier($class->fieldMappings[$fieldName]['columnName']);
        }

        return $class->fieldMappings[$fieldName]['columnName'];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName(ClassMetadata $class, AbstractPlatform $platform): string
    {
        if (isset($class->table['quoted'])) {
            return $platform->quoteIdentifier($class->table['name']);
        }
        $reservedKeyList = $platform->getReservedKeywordsList();
        if ($reservedKeyList->isKeyword($class->table['name'])) {
            return $platform->quoteIdentifier($class->table['name']);
        }

        return $class->table['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSequenceName(array $definition, ClassMetadata $class, AbstractPlatform $platform): string
    {
        if (isset($definition['quoted'])) {
            return $platform->quoteIdentifier($class->table['name']);
        }
        $reservedKeyList = $platform->getReservedKeywordsList();
        if ($reservedKeyList->isKeyword($definition['sequenceName'])) {
            return $platform->quoteIdentifier($definition['sequenceName']);
        }

        return $definition['sequenceName'];
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinColumnName(array|JoinColumnMapping $joinColumn, ClassMetadata $class, AbstractPlatform $platform): string
    {
        if (isset($joinColumn['quoted'])) {
            return $platform->quoteIdentifier($joinColumn['name']);
        }
        $reservedKeyList = $platform->getReservedKeywordsList();
        if ($reservedKeyList->isKeyword($joinColumn['name'])) {
            return $platform->quoteIdentifier($joinColumn['name']);
        }

        return $joinColumn['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getReferencedJoinColumnName(array|JoinColumnMapping $joinColumn, ClassMetadata $class, AbstractPlatform $platform): string
    {
        if (isset($joinColumn['quoted'])) {
            return $platform->quoteIdentifier($joinColumn['referencedColumnName']);
        }
        $reservedKeyList = $platform->getReservedKeywordsList();
        if ($reservedKeyList->isKeyword($joinColumn['referencedColumnName'])) {
            return $platform->quoteIdentifier($joinColumn['referencedColumnName']);
        }

        return $joinColumn['referencedColumnName'];
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinTableName(array|ManyToManyOwningSideMapping $association, ClassMetadata $class, AbstractPlatform $platform): string
    {
        if (isset($association['joinTable']['quoted'])) {
            return $platform->quoteIdentifier($association['joinTable']['name']);
        }
        $reservedKeyList = $platform->getReservedKeywordsList();
        if ($reservedKeyList->isKeyword($association['joinTable']['name'])) {
            return $platform->quoteIdentifier($association['joinTable']['name']);
        }

        return $association['joinTable']['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierColumnNames(ClassMetadata $class, AbstractPlatform $platform): array
    {
        $quotedColumnNames = array();

        foreach ($class->identifier as $fieldName) {
            if (isset($class->fieldMappings[$fieldName])) {
                $quotedColumnNames[] = $this->getColumnName($fieldName, $class, $platform);

                continue;
            }

            // Association defined as Id field
            $joinColumns            = $class->associationMappings[$fieldName]['joinColumns'];
            $assocQuotedColumnNames = array_map(
                function ($joinColumn) use ($platform) {
                    if (isset($joinColumn['quoted'])) {
                        return $platform->quoteIdentifier($joinColumn['name']);
                    }
                    $reservedKeyList = $platform->getReservedKeywordsList();
                    if ($reservedKeyList->isKeyword($joinColumn['name'])) {
                        return $platform->quoteIdentifier($joinColumn['name']);
                    }

                    return $joinColumn['name'];
                },
                $joinColumns
            );

            $quotedColumnNames = array_merge($quotedColumnNames, $assocQuotedColumnNames);
        }

        return $quotedColumnNames;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnAlias($columnName, $counter, AbstractPlatform $platform, ClassMetadata $class = null): string
    {
        // 1 ) Concatenate column name and counter
        // 2 ) Trim the column alias to the maximum identifier length of the platform.
        //     If the alias is to long, characters are cut off from the beginning.
        // 3 ) Strip non alphanumeric characters
        // 4 ) Prefix with "_" if the result its numeric
        $columnName = $columnName.'_'.$counter;
        $columnName = substr($columnName, -$platform->getMaxIdentifierLength());
        $columnName = preg_replace('/[^A-Za-z0-9_]/', '', $columnName);
        $columnName = is_numeric($columnName) ? '_'.$columnName : $columnName;

        return $columnName;
    }
}