<?php

namespace Reach\Payment\Api\Data;

interface DutyResponseInterface
{
    const SUCCESS       = 'success';
    const ERROR_MESSAGE = 'error_message';
    const DUTY          = 'duty';
    const IS_OPTIONAL   = 'optional';

    /**
     * @return bool
     */
    public function getSuccess();

    /**
     * @return void
     */
    public function setSuccess($text);
   
    /**
     * @return string
     */
    public function getErrorMessage();

    /**
     * @return void
     */
    public function setErrorMessage($text);

    /**
     * @return float
     */
    public function getDuty();

    /**
     * @return float
     */
    public function setDuty($duty);

     /**
      * @return boolean
      */
    public function getIsOptional();

    /**
     * @return boolean
     */
    public function setIsOptional($status);
}
