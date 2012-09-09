<?php

require_once 'Model/WSDLClass.php';
require_once 'Model/WSDLService.php';
require_once 'Model/WSDLServiceFunction.php';
require_once 'Lib/WSDLInterpreter.php';
require_once 'Lib/WSDLInterpreterException.php';

/**
 * This class generate Magento classes from WSDL file.
 *
 * Objects classes and services classes
 *
 * @package     Level42
 * @subpackage  Wsdl2Magento
 */
class Level42_Wsdl2Magento_Wsdl2Magento
{
  /**
   * Name of the wsdl object parent class
   * @var string
   */
  const BASE_CLASSNAME = 'WSObject';

  /**
   * Configuration file DomDocument
   * @var DOMDocument
   */
  private $_file = null;

  /**
   * Configuration node
   * @var SimpleXMLElement
   */
  private $_conf = null;

  /**
   * Default environment
   * @var string
   */
  private $_env = 'default';

  /**
   * Level level required
   * @var string
   */
  private $_logLevel = self::LOG_LEVEL_INFO;

  /**
   * Log level DEBUG
   * @var string
   */
  const LOG_LEVEL_DEBUG = 'debug';

  /**
   * Log level ERROR
   * @var string
   */
  const LOG_LEVEL_ERROR = 'error';

  /**
   * Log level WARNING
   * @var string
   */
  const LOG_LEVEL_WARN = 'warning';

  /**
   * Log level INFO
   * @var string
   */
  const LOG_LEVEL_INFO = 'info';

  /**
   * Hierachy of log levels
   * @var string[]
   */
  private $_logsPriorities = array(
      self::LOG_LEVEL_DEBUG,
      self::LOG_LEVEL_INFO,
      self::LOG_LEVEL_WARN,
      self::LOG_LEVEL_ERROR
  );

  /**
   * Path to the configuration file
   * @var string
   */
  private $_confFile = '';

  /**
   * Generator constructor
   *
   * @param string $env      Used environment ('default' by default)
   * @param string $logLevel Log level required
   */
  public function __construct($env = 'default', $logLevel = self::LOG_LEVEL_INFO)
  {
    $this->_confFile = __DIR__.'/etc/wsdl2magento.xml';
    
    $this->_file = simplexml_load_file($this->_confFile);
    if ($this->_file == false) {
      $this->addLog(self::LOG_LEVEL_ERROR, "Config file " . self::CONFIG_PATH . " not loaded");
      throw new Exception("Config file " . $this->_confFile . " not loaded");
    }
    $this->_env = $env;
    $this->_logLevel = $logLevel;
  }

  /**
   * Launch generator processor
   * @throws Exception
   */
  public function execute()
  {
    $configuration = $this->_file->xpath('//configuration[@env="'.$this->_env.'"]/webservices');

    if (count($configuration) == 1) {
      $this->_conf = $configuration[0];
    } else {
      $this->addLog(self::LOG_LEVEL_ERROR, "Config file " . self::CONFIG_PATH . " not correctly formatted for ".$this->_env." environment");
      throw new Exception("Config file " . self::CONFIG_PATH . " not correctly formatted for ".$this->_env." environment");
    }

    if ($this->_conf == null) {
      $this->addLog(self::LOG_LEVEL_ERROR, "Environment " . $this->_env . " not find in the configuration file");
      throw new Exception("Environment " . $this->_env . " not find in the configuration file");
    }

    foreach ($this->_conf->children() as $webservice) {
      /* @var $webservice SimpleXMLElement */
      $name = $webservice->attributes()->name;
      $this->addLog(self::LOG_LEVEL_INFO, "Generate classes for $name");

      $wsdl = $webservice->wsdl;
      $output = $webservice->output;
      $outputBases = $webservice->outputBases;
      $namespace = $webservice->namespace;
      $namespaceBases = $webservice->namespaceBases;
      $parentSoapClass = $webservice->parentSoapClass;

      $this->addLog(self::LOG_LEVEL_DEBUG, "WSDL : $wsdl");
      $this->addLog(self::LOG_LEVEL_DEBUG, "Parent SoapClient class : $parentSoapClass");
      $this->addLog(self::LOG_LEVEL_DEBUG, "Ouput : $output");
      $this->addLog(self::LOG_LEVEL_DEBUG, "Output bases : $outputBases");
      $this->addLog(self::LOG_LEVEL_DEBUG, "Namespace : $namespace");
      $this->addLog(self::LOG_LEVEL_DEBUG, "Namespace bases : $namespaceBases");

			try {
				$this->generate($wsdl, $output, $outputBases, $namespace, $namespaceBases, $parentSoapClass);

				$this->addLog(self::LOG_LEVEL_INFO, "Generate base WS object class");
				$this->generateBaseClass($output, $namespace);

				$this->addLog(self::LOG_LEVEL_INFO, "Generated");
      } catch (Level42_Wsdl2Magento_Lib_WSDLInterpreterException $ex) {
				$this->addLog(self::LOG_LEVEL_ERROR, "Exception : ".$ex->getMessage());
      }
    }

    $this->addLog(self::LOG_LEVEL_INFO, "End of generation");

  }

  /**
   * Launch class generation for a WSDL
   *
   * @param string $wsdl             WSDL to use
   * @param string $output           Output directory for classes
   * @param string $output_bases     Output directory for parent classes
   * @param string $namespace        Namespace for classes
   * @param string $namespace_bases  Namespace for parent classes
   */
  protected function generate($wsdl, $output, $outputBases, $namespace, $namespaceBases, $parentSoapClass = 'SoapClient')
  {
    $this->addLog(self::LOG_LEVEL_INFO, 'Prepare output directories');
    $this->prepareDirectories($output);
    $this->prepareDirectories($outputBases, true);

    $this->addLog(self::LOG_LEVEL_INFO, 'Read '.$wsdl.' WSDL');
    $parser = new Level42_Wsdl2Magento_Lib_WSDLInterpreter($wsdl, $namespace, $namespaceBases, $namespace.self::BASE_CLASSNAME, $parentSoapClass);

    $this->addLog(self::LOG_LEVEL_INFO, 'Save classes to '.$output);
    $parser->savePHPObjects($output, $outputBases);

    $this->addLog(self::LOG_LEVEL_INFO, 'Save base classes to '.$outputBases);
    $parser->savePHPServices($output, $outputBases);
  }

  /**
   * Initialize output directories
   *
   * Build path tree
   * Delete parent directories files
   *
   * @param string $path            Tree to build
   * @param boolean $delete_content Content must be deleted ?
   */
  protected function prepareDirectories($path, $deleteContent = false)
  {
    if (!is_dir($path)) {
      $this->addLog(self::LOG_LEVEL_DEBUG, 'Create output directory '.$path);
      mkdir($path, 0777, true);
    } else {
      $this->addLog(self::LOG_LEVEL_DEBUG, 'Output directory allready exist '.$path);
      if ($deleteContent) {
        $this->addLog(self::LOG_LEVEL_DEBUG, 'Delete directory content '.$path);
        $objects = scandir($path);
        foreach ($objects as $object) {
          if ($object != "." && $object != "..") {
            if (is_file($path.DIRECTORY_SEPARATOR.$object)) {
              unlink($path.DIRECTORY_SEPARATOR.$object);
            }
          }
        }
      }
    }
  }

  /**
   * Generate high level base class for Wsdl object
   *
   * @param string $path      Storing class path
   * @param string $namespace Parent classe namespace
   */
  protected function generateBaseClass($path, $namespace)
  {
    $filename = $path.DIRECTORY_SEPARATOR.self::BASE_CLASSNAME.'.php';
    if (!file_exists($filename)) {
      $content = "<?php\n\n/**\n * Base class for parent webservice objects\n */\nclass ".$namespace.self::BASE_CLASSNAME." {\n}";
      file_put_contents($filename, $content);
    }
  }

  /**
   * Add a log
   *
   * @param string $level   Log message level
   * @param string $message Log message content
   */
  protected function addLog($level, $message)
  {
    if (array_search($this->_logLevel, $this->_logsPriorities) <= array_search($level, $this->_logsPriorities)) {
      echo "[".str_pad($level, 5, ' ')."] ".date("Y-m-d G:i:s")." $message\n";
    }
  }

}