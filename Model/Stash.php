<?php

namespace Reach\Payment\Model;

class Stash implements \Reach\Payment\Api\StashInterface
{
    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;

    /**
     * @var \Magento\Framework\Session\SessionManager
     */
    protected $session;

    /**
     * @var \Reach\Payment\Api\Data\StashResponseInterface
     */
    protected $response;
   
    /**
     * Constructor
     *
     * @param \Reach\Payment\Helper\Data $reachHelper
     * @param \Magento\Framework\Session\SessionManager $sessionManager
     * @param \Reach\Payment\Api\Data\StashResponseInterface $response
     */
    public function __construct(
        \Reach\Payment\Helper\Data $reachHelper,
        \Magento\Framework\Session\SessionManager $sessionManager,
        \Reach\Payment\Api\Data\StashResponseInterface $response
    ) {
        $this->reachHelper  = $reachHelper;
        $this->session          = $sessionManager;
        $this->response         = $response;
    }

    /**
     * @inheritDoc
     */
    public function getStash()
    {
        $stashId = $this->reachHelper->getMerchantId().'/'.$this->session->getSessionId();
        $this->response->setSuccess(true);
        $this->response->setStash($stashId);
        return $this->response;
    }
}
