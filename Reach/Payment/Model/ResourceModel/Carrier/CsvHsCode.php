<?php

namespace Reach\Payment\Model\ResourceModel\Carrier;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\DirectoryList;
use Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode\Import;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @api
 * @since 100.0.2
 */
class CsvHsCode extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Count of imported hscodes rates
     *
     * @var int
     */
    protected $_importedRows = 0;

    /**
     * @var \Psr\Log\LoggerInterface
     * @since 100.1.0
     */
    protected $logger;

    /**
     * @var Import
     */
    private $import;
    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory
     */
    protected $readFactory;

    protected $_objectManager;


    /**
     * Constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     * @param Import $import
     * @param \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory
     * @param \Magento\Framework\ObjectManagerInterface $_objectManager
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Psr\Log\LoggerInterface $logger,
        Import $import,
        \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory,
        \Magento\Framework\ObjectManagerInterface $_objectManager,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->logger = $logger;
        $this->import = $import;
        $this->readFactory = $readFactory;
        $this->_objectManager = $_objectManager;
    }

    /**
     * Define main table and id field name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('reach_hs_code', 'id');
    }

    /**
     * @param array $fields
     * @param array $values
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    private function importData(array $fields, array $values)
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            if (count($fields) && count($values)) {
                $connection->delete($this->getMainTable());
                $this->getConnection()->insertArray($this->getMainTable(), $fields, $values);
                $this->_importedRows += count($values);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $connection->rollBack();
            throw new \Magento\Framework\Exception\LocalizedException(__('Unable to import data'), $e);
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while importing HS codes.')
            );
        } 
        $connection->commit();
    }

    /**
     * Upload HS code file and import data from it
     *
     * @param \Magento\Framework\DataObject $object
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return this
     */
    public function uploadAndImport(\Magento\Framework\DataObject $object)
    {
        
        if (empty($_FILES['groups']['tmp_name']['reach_payment']['groups']['reach_dhl']['fields']['import_csv_hs_code']['value'])) {
            return $this;
        }

        $filePath = $_FILES['groups']['tmp_name']['reach_payment']['groups']['reach_dhl']['fields']['import_csv_hs_code']['value'];
           
        try {
            $file = $this->getCsvFile($filePath);
            $columns = $this->import->getColumns();
            foreach ($this->import->getData($file) as $bunch) {
                $this->importData($columns, $bunch);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while importing hs codes.'.$e->getMessage())
            );
        }

        if ($this->import->hasErrors()) {
            $error = __(
                'We couldn\'t import this file because of these errors: %1',
                implode(" \n", $this->import->getErrors())
            );
            throw new \Magento\Framework\Exception\LocalizedException($error);
        }
    }

    /**
     * @param string $filePath
     * @return \Magento\Framework\Filesystem\File\ReadInterface
     */
    private function getCsvFile($filePath)
    {
        $pathInfo = pathinfo($filePath);
        $dirName = isset($pathInfo['dirname']) ? $pathInfo['dirname'] : '';
        $fileName = isset($pathInfo['basename']) ? $pathInfo['basename'] : '';
        $directoryRead = $this->getDirectoryReadByPath($dirName);
        return $directoryRead->openFile($fileName);
    }

    /**
     * Create an instance of directory with read permissions by path.
     *
     * @param string $path
     * @param string $driverCode
     *
     * @return \Magento\Framework\Filesystem\Directory\ReadInterface
     *
     */
    public function getDirectoryReadByPath($path, $driverCode = DriverPool::FILE)
    {
        return $this->readFactory->create($path, $driverCode);
    }

    /**
     * Save import data batch
     *
     * @param array $data
     * @return \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode
     */
    protected function _saveImportData(array $data)
    {
        if (!empty($data)) {
            $columns = [
                'id',
                'sku',
                'hs_code'
            ];
            $connection->delete($this->getMainTable());
            $this->getConnection()->insertArray($this->getMainTable(), $columns, $data);
            $this->_importedRows += count($data);
        }

        return $this;
    }
}
