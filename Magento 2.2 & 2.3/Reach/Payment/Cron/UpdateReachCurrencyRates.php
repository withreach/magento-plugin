<?php

namespace Reach\Payment\Cron;

class UpdateReachCurrencyRates
{
    /**
     * @var  \Reach\Payment\Model\Currency
     */
    protected $currencyModel;

    public function __construct(
        \Reach\Payment\Model\Currency $currencyModel
    ) {
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
            //$logger->info('Its called');
            $this->currencyModel->updateRates();
        } catch (\Exception $e) {
            //$logger->info($e->getMessage());
        }
    }
}
