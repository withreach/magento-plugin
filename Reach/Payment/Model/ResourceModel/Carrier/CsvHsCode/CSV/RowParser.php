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
     *  @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_logger = $logger;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return [
            'sku',
            'hs_code',
            'country_of_origin'
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
        $countryOfOrigin = $this->getCountryOfOrigin($rowData, $columnResolver);

        return [
            'sku' => $sku,
            'hs_code' => $hsCode,
            'country_of_origin' => $countryOfOrigin,
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
            $hsCode = null;
        }
        return $hsCode;
    }

    /**
     * @param array $rowData
     * @param ColumnResolver $columnResolver
     * @return int|string
     * @throws ColumnNotFoundException
     */
    private function getCountryOfOrigin(array $rowData, ColumnResolver $columnResolver)
    {
        $countryOfOrigin = $columnResolver->getColumnValue(ColumnResolver::COLUMN_COUNTRYOFORIGIN, $rowData);
        if (strlen($countryOfOrigin) != 2) {
            if(strlen($countryOfOrigin) > 0){
                $this->_logger->debug('----------------IMPORT ERROR----------------');
                $sku = $columnResolver->getColumnValue(ColumnResolver::COLUMN_SKU, $rowData);
                $this->_logger->debug("Country of origin for sku ".$sku." was inputted incorrectly: ".$countryOfOrigin);
                $this->_logger->debug('================END OF IMPORT ERROR================');
            }
            $countryOfOrigin = null;
        } else {
            $countryOfOrigin = strtoupper($countryOfOrigin);
        }
        return $countryOfOrigin;
    }
}
