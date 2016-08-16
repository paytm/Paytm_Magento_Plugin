<?php
//script to create new table
$installer = $this;
$installer->startSetup();
$installer->run("
-- DROP TABLE IF EXISTS `paytm`;
CREATE TABLE `paytm` (
  `paytm_id` bigint(20) unsigned NOT NULL auto_increment,
  `cust_id` varchar(100),
  PRIMARY KEY (`paytm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
"
);
$installer->endSetup();
