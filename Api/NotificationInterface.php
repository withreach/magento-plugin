<?php

namespace Reach\Payment\Api;

use Reach\Payment\Api\Data\ResponseInterface;

/**
 * @api
 */
interface NotificationInterface
{
    /**
     * @param string $request
     * @param string $signature
     * @return ResponseInterface
     */
    public function handleNotification($request, $signature);
}
