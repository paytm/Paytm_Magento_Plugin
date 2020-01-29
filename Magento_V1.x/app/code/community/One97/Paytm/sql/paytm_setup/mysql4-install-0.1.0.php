<?php
//script to create new table
$tableName = Mage::getSingleton('core/resource')->getTableName('paytm_order_data');
$installer = $this;
$installer->startSetup();
$installer->run("
DROP TABLE IF EXISTS `paytm`;
DROP TABLE IF EXISTS `".$tableName."`;
CREATE TABLE `".$tableName."` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`order_id` text NOT NULL,
	`paytm_order_id` text NOT NULL,
	`transaction_id` text,
	`status` tinyint(1) DEFAULT 0,
	`paytm_response` text,
	`date_added` datetime DEFAULT CURRENT_TIMESTAMP,
	`date_modified` datetime DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
"
);
$installer->endSetup();
