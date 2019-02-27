<?php

namespace Reach\Payment\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Db\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();
        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $quoteAddressTable = 'quote_address';
            $quoteTable = 'quote';
            $orderTable = 'sales_order';
            $invoiceTable = 'sales_invoice';
            $creditmemoTable = 'sales_creditmemo';

        //Setup two columns for quote, quote_address and order
        //Quote address tables
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($quoteAddressTable),
                'reach_duty',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length'=>'12,4',
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' =>'Tax & Duty'
                ]
            );
        
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($quoteAddressTable),
                'base_reach_duty',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length'=>'12,4',
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' =>'Base Tax & Duty'
                ]
            );

            $setup->getConnection()
            ->addColumn(
                $setup->getTable($quoteAddressTable),
                'dhl_quote_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    'nullable' => true,
                    'comment' =>'DHL Quote Id'
                ]
            );
        
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($quoteAddressTable),
                'dhl_breakdown',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    'nullable' => true,
                    'comment' =>'DHL Breakdown'
                ]
            );

        //Quote tables
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($quoteTable),
                'reach_duty',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length'=>'12,4',
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' =>'Tax & Duty'

                ]
            );
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($quoteTable),
                'base_reach_duty',
                [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length'=>'12,4',
                'default' => 0.00,
                'nullable' => true,
                'comment' =>'Base Tax & Duty'

                ]
            );
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($quoteTable),
                'dhl_quote_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    'nullable' => true,
                    'comment' =>'DHL Quote Id'
                ]
            );

            $setup->getConnection()
            ->addColumn(
                $setup->getTable($quoteTable),
                'dhl_breakdown',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    'nullable' => true,
                    'comment' =>'DHL Breakdown'
                ]
            );

        //Order tables
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($orderTable),
                'reach_duty',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length'=>'12,4',
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' =>'Tax & Duty'

                ]
            );

            $setup->getConnection()
            ->addColumn(
                $setup->getTable($orderTable),
                'base_reach_duty',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length'=>'12,4',
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' =>'Base Tax & Duty'

                ]
            );

            $setup->getConnection()
            ->addColumn(
                $setup->getTable($orderTable),
                'dhl_quote_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    'nullable' => true,
                    'comment' =>'DHL Quote Id'
                ]
            );

            $setup->getConnection()
            ->addColumn(
                $setup->getTable($orderTable),
                'dhl_breakdown',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    'nullable' => true,
                    'comment' =>'DHL Breakdown'
                ]
            );


        //Invoice tables
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($invoiceTable),
                'reach_duty',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length'=>'12,4',
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' =>'Tax & Duty'

                ]
            );
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($invoiceTable),
                'base_reach_duty',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length'=>'12,4',
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' =>'Tax & Duty'

                ]
            );
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($invoiceTable),
                'dhl_quote_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    'nullable' => true,
                    'comment' =>'DHL Quote Id'
                ]
            );

            $setup->getConnection()
            ->addColumn(
                $setup->getTable($invoiceTable),
                'dhl_breakdown',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    'nullable' => true,
                    'comment' =>'DHL Breakdown'
                ]
            );

        //Credit memo tables
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($creditmemoTable),
                'reach_duty',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length'=>'12,4',
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' =>'Tax & Duty'

                ]
            );
       
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($creditmemoTable),
                'base_reach_duty',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length'=>'12,4',
                    'default' => 0.00,
                    'nullable' => true,
                    'comment' =>'Tax & Duty'

                ]
            );

            $setup->getConnection()
            ->addColumn(
                $setup->getTable($creditmemoTable),
                'dhl_quote_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    'nullable' => true,
                    'comment' =>'DHL Quote Id'
                ]
            );
            
            $setup->getConnection()
            ->addColumn(
                $setup->getTable($creditmemoTable),
                'dhl_breakdown',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    'nullable' => true,
                    'comment' =>'DHL Breakdown'
                ]
            );
        }
         $installer->endSetup();
    }
}
