<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode\CSV;

use ColumnNotFoundException;

class ColumnResolver
{
    const COLUMN_ID = 'Id';
    const COLUMN_SKU = 'sku';
    const COLUMN_HSCODE = 'hs_code';
    const COLUMN_COUNTRYOFORIGIN = 'country_of_origin';

    /**
     * @var array
     */
    private $nameToPositionIdMap = [
        self::COLUMN_ID => 0,
        self::COLUMN_SKU => 1,
        self::COLUMN_HSCODE => 2,
        self::COLUMN_COUNTRYOFORIGIN => 3,
    ];

    /**
     * @var array
     */
    private $headers;

    /**
     * ColumnResolver constructor.
     * @param array $headers
     * @param array $columns
     */
    public function __construct(array $headers, array $columns = [])
    {
        $this->nameToPositionIdMap = array_merge($this->nameToPositionIdMap, $columns);
        $this->headers = array_map('trim', $headers);
    }

    /**
     * @param string $column
     * @param array $values
     * @return string|int|float|null
     * @throws \ColumnNotFoundException
     */
    public function getColumnValue($column, array $values)
    {
        $column = (string) $column;
        $columnIndex = array_search($column, $this->headers, true);
        if (false === $columnIndex) {
            if (array_key_exists($column, $this->nameToPositionIdMap)) {
                $columnIndex = $this->nameToPositionIdMap[$column];
            } else {
                throw new ColumnNotFoundException(__('Requested column "%1" cannot be resolved', $column));
            }
        }

        if (!array_key_exists($columnIndex, $values)) {
            throw new ColumnNotFoundException(__('Column "%1" not found', $column));
        }

        return  trim($values[$columnIndex]);
    }
}
