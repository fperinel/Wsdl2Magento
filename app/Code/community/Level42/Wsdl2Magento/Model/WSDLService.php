<?php

/**
 * This class represent a service class
 *
 * @package     Level42
 * @subpackage  Wsdl2Magento.Model
 */
class Level42_Wsdl2Magento_Model_WSDLService
{

  /**
   * Name for the class file
   * @var string
   */
  private $_fileName = null;

  /**
   * Name for the class
   * @var string
   */
  private $_name = null;

  /**
   * Name of the extended class
   * @var string
   */
  private $_extends = null;

  /**
   * Name of the class
   * @var string
   */
  private $_content = null;

  /**
   * Is a base class ?
   * @var boolean
   */
  private $_base = false;

  /**
   * Webservice namespace
   *
   * @var string
   */
  private $_serviceNamespace = null;

  /**
   * List of Webservcie classmap
   *
   * @var string[]
   */
  private $_classmap = array();

  /**
   * URL of the webservice
   *
   * @var string
   */
  private $_wsdl = null;

  /**
   * Name of the remote service
   *
   * @var string
   */
  private $_serviceName = null;

  /**
   * List of functions
   *
   * @var Level42_Wsdl2Magento_Model_WSDLServiceFunction
   */
  private $_functions = array();

  /**
   * Constructor
   *
   * @param string $fileName     Class filename
   * @param string $name         Class name
   * @param string $extends      Parent of the class
   * @param Level42_Wsdl2Magento_Model_WSDLServiceFunction $functions List of service function
   * @param boolean $base        True if is a base class
   * @param string $wsdl         WSDL use to generate the service
   * @param string $serviceName  Name of remote services
   * @param string $serviceNamespace Namespace of the service
   * @param string[] $classmap   Classmap, links beetwen PHP class and WSDL class
   */
  public function __construct($fileName, $name, $extends = null, $functions = array(), $base = false, $wsdl = '', $serviceName = '', $serviceNamespace = '', $classmap = array())
  {
    $this->_fileName = $fileName;
    $this->_name = $name;
    $this->_extends = $extends;
    $this->_base = $base;
    $this->_serviceNamespace = $serviceNamespace;
    $this->_classmap = $classmap;
    $this->_wsdl = $wsdl;
    $this->_serviceName = $serviceName;
    $this->_functions = $functions;
  }

  /**
   * Generate output file
   *
   * @param string $outputDir  Output directory for the class file
   * @param boolean $overwrite Overwrite an existing file
   */
  public function output($outputDir, $overwrite = false)
  {
    if (!file_exists($this->getFullPathFilename($outputDir)) || $overwrite) {
      file_put_contents($this->getFullPathFilename($outputDir), "<?php\n\n".$this->getContent());
    }
  }

  /**
   * Return filename with all path
   *
   * @param string $outputDir  Output directory for the class file
   *
   * @return string Filename with all path directory for the class file
   */
  public function getFullPathFilename($outputDir)
  {
    return $outputDir.DIRECTORY_SEPARATOR.$this->_fileName.'.php';
  }

  /**
   * Return true if this class is a parent class
   *
   * @return boolean
   */
  public function isBaseClass()
  {
    return $this->_base;
  }

  /**
   * Generate and return the code content of the object class
   *
   * @return string Code content of the object class
   */
  public function getContent()
  {
    return $this->isBaseClass() ? $this->generateBaseContent() : $this->generateContent();
  }

  /**
   * Generate the code content of the parent object class
   *
   * @return string Code content of the parent object class
   */
  protected function generateBaseContent()
  {
    if ($this->_content == null) {
      $this->_content = "";
      $this->_content .= "/**\n";
      $this->_content .= " * Service ".$this->_name."\n";
      $this->_content .= " *\n";
      $this->_content .= " * Generated on".date("Y-m-d G:i:s")."\n";
      $this->_content .= " * from ".$this->_wsdl."\n";
      $this->_content .= " *\n";
      $this->_content .= " * This class is automatically generated\n";
      $this->_content .= " * Do not modify or commit !\n";
      $this->_content .= " */\n";
      $this->_content .= "class ".$this->_name." extends ".$this->_extends." {\n";

      $this->_content .= "  /**\n";
      $this->_content .= "   * Webservice namespace\n";
      $this->_content .= "  ' * @access private\n";
      $this->_content .= "   * @var string\n";
      $this->_content .= "   */\n";
      $this->_content .= "  private \$namespace = '".$this->_serviceNamespace."';\n\n";

      if (sizeof($this->_classmap) > 0) {
        $this->_content .= "  /**\n";
        $this->_content .= "   * Default class map for wsdl=>php\n";
        $this->_content .= "   * @access private\n";
        $this->_content .= "   * @var array\n";
        $this->_content .= "   */\n";
        $this->_content .= "  private static \$classmap = array(\n";
        foreach ($this->_classmap as $className => $validClassName) {
          $this->_content .= "    '".$className."' => '".$validClassName."',\n";
        }
        $this->_content .= "  );\n\n";
      }

      $this->_content .= "  ".'/**'."\n";
      $this->_content .= "  ".' * Constructor using wsdl location and options array'."\n";
      $this->_content .= "  ".' * @param string $wsdl WSDL location for this service'."\n";
      $this->_content .= "  ".' * @param array $options Options for the SoapClient'."\n";
      $this->_content .= "  ".' */'."\n";
      $this->_content .= "  ".'public function __construct($wsdl="'.str_replace("\\", "\\\\", $this->_wsdl).'", $options=array()) {'."\n";
      $this->_content .= "    ".'foreach (self::$classmap as $wsdlClassName => $phpClassName) {'."\n";
      $this->_content .= "    ".'  if (!isset($options[\'classmap\'][$wsdlClassName])) {'."\n";
      $this->_content .= "    ".'    $options[\'classmap\'][$wsdlClassName] = $phpClassName;'."\n";
      $this->_content .= "    ".'  }'."\n";
      $this->_content .= "    ".'}'."\n";
      if ($this->_serviceName != null) {
        $this->_content .= "    ".'if (!isset($options[\'location\'])) {'."\n";
        $this->_content .= "    ".'  $options[\'location\'] = \''.$this->_serviceName.'\'; '."\n";
        $this->_content .= "    ".'}'."\n";
      }
      $this->_content .= "    ".'parent::__construct($wsdl, $options);'."\n";
      $this->_content .= "  }\n\n";

      foreach ($this->_functions as $function) {
        $this->_content .= $function->getContent()."\n";
      }
      
      $this->generateBaseContentEmbededFunction();

      $this->_content .= "}";
    }
    return $this->_content;
  }

  /**
   * Generate the embeded functions of the class
   */
  protected function generateBaseContentEmbededFunction()
  {
    $this->_content .= "  ".'/**'."\n";
    $this->_content .= "  ".' * Set a SOAP header'."\n";
    $this->_content .= "  ".' * '."\n";
    $this->_content .= "  ".' * @param string $key Key of header value'."\n";
    $this->_content .= "  ".' * @param mixed $value Value of header'."\n";
    $this->_content .= "  ".' */'."\n";
    $this->_content .= "  ".'public function setSoapHeader($key, $value = null) {'."\n";
    $this->_content .= "    ".'$soapHeader = new SoapHeader($this->namespace, $key, $value);'."\n";
    $this->_content .= "    ".'$this->__setSoapHeaders($soapHeader);'."\n";
    $this->_content .= "  }\n\n";

    $this->_content .= "  ".'/**'."\n";
    $this->_content .= "  ".' * Set multiple SOAP headers'."\n";
    $this->_content .= "  ".' * '."\n";
    $this->_content .= "  ".' * @param array $header  Associative array with key/value'."\n";
    $this->_content .= "  ".' */'."\n";
    $this->_content .= "  ".'public function setSoapHeaders($headers = array()) {'."\n";
    $this->_content .= "    ".'$headers = array();'."\n";
    $this->_content .= "    ".'foreach ($headers as $key => $value) {'."\n";
    $this->_content .= "      ".'$headers[] = new SoapHeader($this->namespace, $key, $value);'."\n";
    $this->_content .= "    }\n";
    $this->_content .= "    ".'$this->__setSoapHeaders($headers);'."\n";
    $this->_content .= "  }\n\n";

    $this->_content .= "  /**\n";
    $this->_content .= "   * This function is called before webservice call\n";
    $this->_content .= "   * Can be ovewrited on the child service class\n";
    $this->_content .= "   * \n";
    $this->_content .= "   * @param string \$className    Name of the service class\n";
    $this->_content .= "   * @param string \$functionName Name of the function called\n";
    $this->_content .= "   * @param array  \$args         List of arguments sent to function\n";
    $this->_content .= "   */\n";
    $this->_content .= "  protected function _beforeCall(\$className, \$functionName, \$args) { }\n\n";

    $this->_content .= "  /**\n";
    $this->_content .= "   * This function is called after webservice call\n";
    $this->_content .= "   * Can be ovewrited on the child service class\n";
    $this->_content .= "   * \n";
    $this->_content .= "   * @param string \$className    Name of the service class\n";
    $this->_content .= "   * @param string \$functionName Name of the function called\n";
    $this->_content .= "   * @param array  \$result       Result of the function call\n";
    $this->_content .= "   */\n";
    $this->_content .= "  protected function _afterCall(\$className, \$functionName, \$result) { }\n\n";
  }

  /**
   * Generate the code content of the parent object class
   *
   * @return string Code content of the parent object class
   */
  protected function generateContent()
  {
    if ($this->_content == null) {
      $this->_content = "";
      $this->_content .= "/**\n";
      $this->_content .= " * Service ".$this->_name."\n";
      $this->_content .= " */\n";
      $this->_content .= "class ".$this->_name." extends ".$this->_extends." {\n";
      $this->_content .= "  /**\n";
      $this->_content .= "   * Overwrite the parent class here\n";
      $this->_content .= "   * This class is never overwrite\n";
      $this->_content .= "   */\n";
      $this->_content .= "}";
    }
    return $this->_content;
  }
}