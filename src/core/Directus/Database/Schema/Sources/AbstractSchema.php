<?php

namespace Directus\Database\Schema\Sources;

use Directus\Database\Schema\Object\Field;
use Directus\Util\ArrayUtils;

abstract class AbstractSchema implements SchemaInterface
{
    /**
     * Cast records values by its column data type
     *
     * @param array    $records
     * @param Field[] $fields
     *
     * @return array
     */
    public function castRecordValues(array $records, $fields)
    {
        // hotfix: records sometimes are no set as an array of rows.
        $singleRecord = false;
        if (!ArrayUtils::isNumericKeys($records)) {
            $records = [$records];
            $singleRecord = true;
        }

        foreach ($fields as $field) {
            foreach ($records as $index => $record) {
                $fieldName = $field->getName();
                if (ArrayUtils::has($record, $fieldName)) {
                    $records[$index][$fieldName] = $this->castValue($record[$fieldName], $field->getType());
                }
            }
        }

        return $singleRecord ? reset($records) : $records;
    }

    /**
     * Parse records value by its column data type
     *
     * @see AbastractSchema::castRecordValues
     *
     * @param array $records
     * @param $columns
     *
     * @return array
     */
    public function parseRecordValuesByType(array $records, $columns)
    {
        return $this->castRecordValues($records, $columns);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLengths()
    {
        return [
            // 'ALIAS' => static::INTERFACE_ALIAS,
            // 'MANYTOMANY' => static::INTERFACE_ALIAS,
            // 'ONETOMANY' => static::INTERFACE_ALIAS,

            // 'BIT' => static::INTERFACE_TOGGLE,
            // 'TINYINT' => static::INTERFACE_TOGGLE,

            // 'MEDIUMBLOB' => static::INTERFACE_BLOB,
            // 'BLOB' => static::INTERFACE_BLOB,

            // 'TINYTEXT' => static::INTERFACE_TEXT_AREA,
            // 'TEXT' => static::INTERFACE_TEXT_AREA,
            // 'MEDIUMTEXT' => static::INTERFACE_TEXT_AREA,
            // 'LONGTEXT' => static::INTERFACE_TEXT_AREA,

            'CHAR' => 1,
            'VARCHAR' => 255,
            // 'POINT' => static::INTERFACE_TEXT_INPUT,

            // 'DATETIME' => static::INTERFACE_DATETIME,
            // 'TIMESTAMP' => static::INTERFACE_DATETIME,

            // 'DATE' => static::INTERFACE_DATE,

            // 'TIME' => static::INTERFACE_TIME,

            // 'YEAR' => static::INTERFACE_NUMERIC,
            // 'SMALLINT' => static::INTERFACE_NUMERIC,
            // 'MEDIUMINT' => static::INTERFACE_NUMERIC,
            'INT' => 11,
            'INTEGER' => 11,
            // 'BIGINT' => static::INTERFACE_NUMERIC,
            // 'FLOAT' => static::INTERFACE_NUMERIC,
            // 'DOUBLE' => static::INTERFACE_NUMERIC,
            // 'DECIMAL' => static::INTERFACE_NUMERIC,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getColumnDefaultLength($type)
    {
        return ArrayUtils::get($this->getDefaultLengths(), strtoupper($type), null);
    }

    /**
     * @inheritdoc
     */
    public function isType($type, array $list)
    {
        return in_array(strtolower($type), $list);
    }

    /**
     * @inheritdoc
     */
    public function getDataType($type)
    {
        switch (strtolower($type)) {
            case 'array':
            case 'json':
                $type = 'text';
                break;
            case 'tinyjson':
                $type = 'tinytext';
                break;
            case 'mediumjson':
                $type = 'mediumtext';
                break;
            case 'longjson':
                $type = 'longtext';
                break;
        }

        return $type;
    }
}
