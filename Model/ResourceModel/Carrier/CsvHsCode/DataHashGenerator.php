<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode;

class DataHashGenerator
{
    /**
     * @param array $data
     * @return string
     */
    public function getHash(array $data)
    {
        $id = $data['id'];
        $sku = $data['sku'];
        $hsCode = $data['hs_code'];
        return sprintf("%s-%d-%s-%F", $id, $sku, $hsCode);
    }
}
