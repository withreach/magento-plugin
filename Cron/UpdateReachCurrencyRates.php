<?php

namespace Reach\Payment\Cron;

class UpdateReachCurrencyRates
{
    /**
     * @var  \Reach\Payment\Model\Currency
     */
    protected $currencyModel;

    /**
     *  @var \Psr\Log\LoggerInterface
     */
    protected $_logger; 

    /**
     * Constructor
     *
     * @param \Reach\Payment\Model\Currency $currencyModel
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Reach\Payment\Model\Currency $currencyModel,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_logger = $logger;
        $this->currencyModel = $currencyModel;
    }
    /**
     * Update reach currencies rates
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->_logger->debug('---------------- updateRates - Function Called ----------------');
            $this->currencyModel->updateRates();
        } catch (\Exception $e) {
            $this->_logger->debug('---------------- updateRates - Error ----------------');
            $this->_logger->debug($e->getMessage());
        }
    }
}
