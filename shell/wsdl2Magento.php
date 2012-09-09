<?php

require_once 'abstract.php';
require_once '../app/code/community/Level42/Wsdl2Magento/Wsdl2Magento.php';

/**
 * Magento class generator from WSDL
 *
 * @category    Mage
 * @package     Mage_Shell
 */
class Mage_Shell_Wsdl2Magento extends Mage_Shell_Abstract
{
  /**
   * Is include Mage and initialize application
   *
   * @var bool
   */
  protected $_includeMage = false;

  /**
   * Run script
   */
  public function run()
  {
    if ($env = $this->getArg('env')) {
      echo "[-----] Launch wsdl2Magento for '".$env."' environment\n";

      try {
        $generator = new Level42_Wsdl2Magento_Wsdl2Magento($env, Level42_Wsdl2Magento_Wsdl2Magento::LOG_LEVEL_INFO);
        $generator->execute();
        echo "[----] Result OK\n";
      } catch (Exception $ex) {
        echo "[fatal] An exception has occurred : ".$ex->getMessage()."\n";
        echo "[-----] Result ERROR\n";
      }


    } else {
      echo $this->usageHelp();
    }
  }

  /**
   * Retrieve Usage Help Message
   *
   */
  public function usageHelp()
  {
    return <<<USAGE
Usage:  php -f wsdl2Magento.php -- [options]
        php -f wsdl2Magento.php -- --env prod

  --env <env>       Define configuration environment to use
  help              This help

USAGE;
  }
}

$shell = new Mage_Shell_Wsdl2Magento();
$shell->run();
