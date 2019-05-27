<?php

namespace Reach\Payment\Model\Api\Data;

use Reach\Payment\Api\Data\DutyResponseInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class DutyResponse extends AbstractExtensibleObject implements DutyResponseInterface
{

    /**
     * @return bool
     */
    public function getSuccess()
    {
        return $this->_get(self::SUCCESS);
    }

    /**
     * @return void
     */
    public function setSuccess($text)
    {
        $this->setData(self::SUCCESS, $text);
    }

    /**
     * @return float
     */
    public function getDuty()
    {
        return $this->_get(self::DUTY);
    }

    /**
     * @return float
     */
    public function setDuty($duty)
    {
        $this->setData(self::DUTY, $duty);
    }

    /**
     * @return boolean
     */
    public function getIsOptional()
    {
        return $this->_get(self::IS_OPTIONAL);
    }

     /**
      * @return boolean
      */
    public function setIsOptional($status)
    {
        $this->setData(self::IS_OPTIONAL, $status);
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->_get(self::ERROR_MESSAGE);
    }

    /**
     * @return void
     */
    public function setErrorMessage($text)
    {
        $this->setData(self::ERROR_MESSAGE, $text);
    }
}
