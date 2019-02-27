<?php

namespace Reach\Payment\Api\Data;

interface StashResponseInterface
{
    const SUCCESS       = 'success';
    const ERROR_MESSAGE = 'error_message';
    const STASH          = 'stash';

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
     * @return string
     */
    public function getStash();

    /**
     * @return string
     */
    public function setStash($stash);
}
