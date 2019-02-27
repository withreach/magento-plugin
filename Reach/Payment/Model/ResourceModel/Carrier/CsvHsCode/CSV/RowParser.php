<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode\CSV;

use Magento\Framework\Phrase;

class RowParser
{
    /**
     * @return array
     */
    public function getColumns()
    {
        return [
            'sku',
            'hs_code',
        ];
    }

    /**
     * @param array $rowData
     * @param ColumnResolver $columnResolver
     * @return array
     * @throws ColumnNotFoundException
     * @throws RowException
     */
    public function parse(
        array $rowData,
        ColumnResolver $columnResolver
    ) {
        
        $id = $this->getId($rowData, $columnResolver);
        $sku = $this->getSku($rowData, $columnResolver);
        $hsCode = $this->getHsCode($rowData, $columnResolver);

        return [
            'sku' => $sku,
            'hs_code' => $hsCode,
        ];
    }
     /**
      * @param array $rowData
      * @param ColumnResolver $columnResolver
      * @return int|null|string
      * @throws ColumnNotFoundException
      */
    private function getId(array $rowData, ColumnResolver $columnResolver)
    {
        $id = $columnResolver->getColumnValue(ColumnResolver::COLUMN_ID, $rowData);
        if ($id === '') {
            $id = '*';
        }
        return $id;
    }

    /**
     * @param array $rowData
     * @param ColumnResolver $columnResolver
     * @return int|string
     * @throws ColumnNotFoundException
     */
    private function getSku(array $rowData, ColumnResolver $columnResolver)
    {
        $sku = $columnResolver->getColumnValue(ColumnResolver::COLUMN_SKU, $rowData);
        if ($sku === '') {
            $sku = '*';
        }
        return $sku;
    }

    /**
     * @param array $rowData
     * @param ColumnResolver $columnResolver
     * @return int|string
     * @throws ColumnNotFoundException
     */
    private function getHsCode(array $rowData, ColumnResolver $columnResolver)
    {
        $hsCode = $columnResolver->getColumnValue(ColumnResolver::COLUMN_HSCODE, $rowData);
        if ($hsCode === '') {
            $hsCode = '*';
        }
        return $hsCode;
    }
}
