<?php

namespace Reach\Payment\Api;

/**
 * @api
 */
interface StashInterface
{
    /**
     * @return \Reach\Payment\Api\Data\StashResponseInterface
     */
    public function getStash();
}
