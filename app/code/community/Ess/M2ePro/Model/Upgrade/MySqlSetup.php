<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Upgrade_MySqlSetup extends Mage_Core_Model_Resource_Setup
{
    private $moduleTables = array();

    //####################################

    public function __construct($resourceName)
    {
        // Get needed mysql tables
        $tempTables = Mage::helper('M2ePro/Module')->getMySqlTables();
        $tempTables = array_merge($this->getMySqlTablesV3(),$tempTables);
        $tempTables = array_values(array_unique($tempTables));

        // Sort by length tables
        do {
            $hasChanges = false;
            for ($i=0;$i<count($tempTables)-1; $i++) {
                if (strlen($tempTables[$i]) < strlen($tempTables[$i+1])) {
                    $temp = $tempTables[$i];
                    $tempTables[$i] = $tempTables[$i+1];
                    $tempTables[$i+1] = $temp;
                    $hasChanges = true;
                }
            }
        } while ($hasChanges);

        // Prepare sql tables
        //--------------------
        foreach ($tempTables as $table) {
            $this->moduleTables[$table] = $this->getTable($table);
        }
        //--------------------

        parent::__construct($resourceName);
    }

    //####################################

    public function startSetup()
    {
        return parent::startSetup();
    }

    public function endSetup()
    {
        Mage::helper('M2ePro')->removeAllCacheValues();
        return parent::endSetup();
    }

    //####################################

    public function run($sql)
    {
        if (trim($sql) == '') {
            return $this;
        }
        $sql = $this->prepareSql($sql);
        $this->_conn->multi_query($sql);
        return $this;
    }

    public function runSqlFile($path)
    {
        if (!is_file($path)) {
            return $this;
        }
        $sql = file_get_contents($path);
        return $this->run($sql);
    }

    //####################################

    public function getModuleTables()
    {
        return $this->moduleTables;
    }

    public function getRelatedSqlFilePath($pathPhpFile)
    {
        return dirname($pathPhpFile).DS.basename($pathPhpFile,'.php').'.sql';
    }

    public function removeConfigDuplicates()
    {
        $this->run(<<<SQL

DROP TABLE IF EXISTS `temp_m2epro_config`;
CREATE TABLE `temp_m2epro_config` (
  id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `group` VARCHAR(255) DEFAULT NULL,
  `key` VARCHAR(255) NOT NULL,
  value VARCHAR(255) DEFAULT NULL,
  notice TEXT DEFAULT NULL,
  update_date DATETIME DEFAULT NULL,
  create_date DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX `group` (`group`),
  UNIQUE INDEX group_key (`group`, `key`),
  INDEX `key` (`key`),
  INDEX value (value)
)
ENGINE = INNODB
CHARACTER SET utf8
COLLATE utf8_general_ci;

INSERT IGNORE INTO `temp_m2epro_config` SELECT * FROM `m2epro_config`;
DELETE FROM `m2epro_config`;
INSERT `m2epro_config` SELECT * FROM `temp_m2epro_config`;
DELETE FROM `temp_m2epro_config`;

INSERT IGNORE INTO `temp_m2epro_config` SELECT * FROM `ess_config`;
TRUNCATE `ess_config`;
INSERT `ess_config` SELECT * FROM `temp_m2epro_config`;
DROP TABLE IF EXISTS `temp_m2epro_config`;

SQL
);
    }

    //####################################

    private function prepareSql($sql)
    {
        foreach ($this->moduleTables as $tableFrom=>$tableTo) {
            $sql = str_replace(' `'.$tableFrom.'`',' `'.$tableTo.'`',$sql);
            $sql = str_replace(' '.$tableFrom,' `'.$tableTo.'`',$sql);
        }
        return $sql;
    }

    private function getMySqlTablesV3()
    {
        return array(
            'ess_config',
            'm2epro_accounts',
            'm2epro_accounts_store_categories',
            'm2epro_config',
            'm2epro_descriptions_templates',
            'm2epro_dictionary_categories',
            'm2epro_dictionary_marketplaces',
            'm2epro_dictionary_shippings',
            'm2epro_dictionary_shippings_categories',
            'm2epro_ebay_items',
            'm2epro_ebay_listings',
            'm2epro_ebay_listings_logs',
            'm2epro_ebay_orders',
            'm2epro_ebay_orders_external_transactions',
            'm2epro_ebay_orders_items',
            'm2epro_ebay_orders_logs',
            'm2epro_feedbacks',
            'm2epro_feedbacks_templates',
            'm2epro_listings',
            'm2epro_listings_categories',
            'm2epro_listings_logs',
            'm2epro_listings_products',
            'm2epro_listings_products_variations',
            'm2epro_listings_products_variations_options',
            'm2epro_listings_templates',
            'm2epro_listings_templates_calculated_shipping',
            'm2epro_listings_templates_payments',
            'm2epro_listings_templates_shippings',
            'm2epro_listings_templates_specifics',
            'm2epro_lock_items',
            'm2epro_marketplaces',
            'm2epro_messages',
            'm2epro_migration_temp',
            'm2epro_products_changes',
            'm2epro_selling_formats_templates',
            'm2epro_synchronizations_logs',
            'm2epro_synchronizations_runs',
            'm2epro_synchronizations_templates',
            'm2epro_templates_attribute_sets'
        );
    }

    //####################################
}
