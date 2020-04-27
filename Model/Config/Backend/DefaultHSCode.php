<?php

namespace Reach\Payment\Model\Config\Backend;


class DefaultHSCode extends \Magento\Framework\App\Config\Value
{
    //To be able to transform config value of interest (by leveraging something called before plugin -
    // in this case plugin specifically for configuration variables) we had to extend the
    // built in \Magento\Framework\App\Config\Value class

    public function beforeSave()
    {
        //Followed hints from https://magently.com/blog/magento-2-backend-configuration-backend-model-part-23/
        //This is Magento's prescribed way of transforming/modifying data or doing something before saving
        // the data into the database table (in this case the table is called `core_config_data`.
        //To see what is getting saved one can execute the following command in the database
        //select * from core_config_data where path like '%hs_code%';
        //the config path (<config_path>reach/dhl/default_hs_code</config_path>) is specified in the system.xml file
        $this->setValue(trim($this->getValue()));
        parent::beforeSave();
    }
}