<?php

namespace Reach\Payment\Model\Api;

class HttpText extends Http
{
    public function __construct(
        \Magento\Framework\HTTP\Adapter\Curl $curl,
        \Reach\Payment\Api\Data\HttpResponseInterface $returnData
    ) {
        parent::__construct($curl, $returnData);

        $this->setContentType("application/x-www-form-urlencoded");
    }

    public function processResponse()
    {
        $data = preg_split('/^\r?$/m', $this->getResponseData(), 2);
        $data = urldecode(trim($data[1]));
        $this->getReturnData()->setStatus($this->getResponseCode());
        $this->getReturnData()->setResponseData($data);
        return $this->getReturnData();
    }
}
