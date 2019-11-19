<?php
 
namespace Reach\Payment\Model\Config\Backend;
 
class CsvFile extends \Magento\Config\Model\Config\Backend\File
{
    /**
     * @return string[]
     */
    public function getAllowedExtensions()
    {
        return ['csv'];
    }
}
