<?php 
    namespace One97\Paytm\Setup;
    use Magento\Framework\Setup\SchemaSetupInterface;
    use Magento\Framework\Setup\ModuleContextInterface;
    use Magento\Framework\DB\Ddl\Table;

    class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface{
        
        /* this function for creating table in DB on enable Paytm module */
        public function install(SchemaSetupInterface $setup,ModuleContextInterface $context){
            $setup->startSetup();
            $conn = $setup->getConnection();
            $tableName = $setup->getTable('paytm_order_data');
            if($conn->isTableExists($tableName) != true){
                $table = $conn->newTable($tableName)
                    ->addColumn(
                        'id',
                        Table::TYPE_INTEGER,
                        null,
                        ['identity'=>true,'unsigned'=>true,'nullable'=>false,'primary'=>true],
                        'ID'
                    )
                    ->addColumn(
                        'order_id',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable'=>false,'default'=>''],
                        'Magento OrderId'
                    )
                    ->addColumn(
                        'paytm_order_id',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable'=>false,'default'=>''],
                        'Paytm OrderId'
                    )
                    ->addColumn(
                        'transaction_id',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable'=>false,'default'=>''],
                        'Paytm TransactionId'
                    )
                    ->addColumn(
                        'status',
                        Table::TYPE_BOOLEAN,
                        null,
                        ['nullable'=>false,'default'=>'0'],
                        'Transaction Status'
                    )
                    ->addColumn(
                        'paytm_response',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable'=>false,'default'=>''],
                        'Paytm Response'
                    )
                    ->addColumn(
                        'date_added',
                        Table::TYPE_DATETIME,
                        null,
                        ['nullable'=>false,'default'=>'0000-00-00 00:00:00'],
                        'Created Dtae'
                    )
                    ->addColumn(
                        'date_modified',
                        Table::TYPE_DATETIME,
                        null,
                        ['nullbale'=>false,'default'=>'0000-00-00 00:00:00'],
                        'Modified Date'
                    )
                    ->setOption('charset','utf8');
                $conn->createTable($table);
            }
            $setup->endSetup();
        }
    }
?>