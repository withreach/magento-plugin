<?php


namespace Reach\Payment\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        $installer = $setup;
        $installer->startSetup();

        /*
         * Create table 'reach_currency_rate'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('reach_currency_rate'))
            ->addColumn(
                'rate_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Rate ID'
            )
            ->addColumn(
                'offer_id',
                Table::TYPE_TEXT,
                50,
                ['nullable' => false],
                'Reach Offer ID'
            )
            ->addColumn(
                'currency',
                Table::TYPE_TEXT,
                3,
                [ 'nullable' => false],
                'Currency Code'
            )
            ->addColumn(
                'rate',
                Table::TYPE_DECIMAL,
                '24,12',
                ['nullable' => false],
                'Currency Rate'
            )
            ->addColumn(
                'expire_at',
                Table::TYPE_TEXT,
                50,
                [ 'nullable' => false],
                'Offer Expiry'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Creation Time'
            )
            ->setComment('Reach Rates Table');
        $installer->getConnection()->createTable($table);

        /*
         * Create table 'reach_open_contract'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('reach_open_contract'))
            ->addColumn(
                'contract_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Contract ID'
            )
            ->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true,'nullable' => false],
                'Customer ID'
            )
            ->addColumn(
                'reach_contract_id',
                Table::TYPE_TEXT,
                36,
                [ 'nullable' => false],
                'Reach Contract ID'
            )
            ->addColumn(
                'currency',
                Table::TYPE_TEXT,
                3,
                [ 'nullable' => false],
                'Currency Code'
            )
             ->addColumn(
                 'method',
                 Table::TYPE_TEXT,
                 25,
                 [ 'nullable' => false],
                 'Payment Method'
             )
            ->addColumn(
                'identifier',
                Table::TYPE_TEXT,
                25,
                [ 'nullable' => false],
                'Payment Method Identifier'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Creation Time'
            )
            ->addColumn(
                'expire_at',
                Table::TYPE_TIMESTAMP,
                null,
                [ 'nullable' => true],
                'Expiry Time'
            )
            ->addIndex(
                $installer->getIdxName('reach_open_contract', ['customer_id']),
                ['customer_id']
            )
            ->addForeignKey(
                $installer->getFkName(
                    'reach_open_contract',
                    'customer_id',
                    'customer_entity',
                    'entity_id'
                ),
                'customer_id',
                $installer->getTable('customer_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Reach Rates Table');
        $installer->getConnection()->createTable($table);
        
          /*
         * Create table 'reach_hs_code'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('reach_hs_code'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Id'
            )
            ->addColumn(
                'sku',
                Table::TYPE_TEXT,
                255,
                [],
                'SKU'
            )
            ->addColumn(
                'hs_code',
                Table::TYPE_TEXT,
                255,
                [],
                'HS Code'
            )
            ->setComment('Reach Hs Code');
        $installer->getConnection()->createTable($table);

        
        $installer->endSetup();
    }
}
