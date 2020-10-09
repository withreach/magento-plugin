<?php

namespace Reach\Payment\Helper;


/**
 * Helper Class
 */
class Misc
{

    /**
     * check IP is local machine IP
     * @param  string $ip
     * @return boolean
     */
    public function checkLocalIP($ip)
    {

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
        {
            // is a local ip address
            return true;
        }
        return false;
    }

}
