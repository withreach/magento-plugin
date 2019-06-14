<?php

namespace Reach\Payment\Model\Api\Data;

use Reach\Payment\Api\Data\StashResponseInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class StashResponse extends AbstractExtensibleObject implements StashResponseInterface
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
     * @return string
     */
    public function getStash()
    {
        return $this->_get(self::STASH);
    }

    /**
     * @return void
     */
    public function setStash($stash)
    {
        $this->setData(self::STASH, $stash);
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
