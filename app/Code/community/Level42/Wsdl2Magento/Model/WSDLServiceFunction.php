<?php

/**
 * This class represent a service class function
 *
 * @package     Level42
 * @subpackage  Wsdl2Magento.Model
 */
class Level42_Wsdl2Magento_Model_WSDLServiceFunction
{

  /**
   * Function name
   *
   * @var string
   */
  private $_name = null;

  /**
   * Function return type
   *
   * @var string
   */
  private $_returnType = 'void';

  /**
   * Function code content
   *
   * @var string
   */
  private $_content = null;

  /**
   * List of function parameters
   *
   * @var string[]
   */
  private $_parameters = array();

  /**
   * List of function exceptions
   *
   * @var string[]
   */
  private $_exceptions = array();

  /**
   * List of primitives PHP types
   *
   * @return string[]
   */
  static function getPrimitive()
  {
    return array('string', 'boolean', 'int', 'dateTime', 'long');
  }

  /**
   * Return true if the parameter is a primitive type
   *
   * @param string $type  Type to analyse
   *
   * @return boolean true if the parameter is a primitive type
   */
  static function isPrimitive($type)
  {
    return in_array($type, self::getPrimitive());
  }

  /**
   * Constructor
   */
  public function __construct($name)
  {
    $this->_name = $name;
  }

  /**
   * Add a parameter to the function
   *
   * @param string $name
   * @param string $type
   */
  public function addParameter($name, $type) 
  {
    $this->_parameters[$name] = $type;
  }

  /**
   * Set the function return type
   *
   * @param string $returnType
   */
  public function setReturnType($returnType) 
  {
    $this->_returnType = $returnType;
  }

  /**
   * Add an exception to the function
   *
   * @param string $type
   */
  public function addException($exception) 
  {
    $this->_exceptions[] = $exception;
  }

  /**
   * Generate the code content of the parent object class
   *
   * @return string Code content of the parent object class
   */
  public function getContent() 
  {
    if ($this->_content == null) {
      $this->_content = "";
      $this->_content .= "  /**\n";
      $this->_content .= "   * Service Call: ".$this->_name."\n";
      $this->_content .= "   *\n";

      // List all function arguments
      foreach ($this->_parameters as $name => $type) {
        $this->_content .= "   * @param ".$type." $".$name."\n";

        if (self::isPrimitive($type)) {
          $arguments[] = "$".$name;
        } else {
          $arguments[] = $type." $".$name;
        }
      }

      $this->_content .= "   *\n";
      $this->_content .= "   * @return ".$this->_returnType." Soap method result\n";
      $this->_content .= "   *\n";
      foreach ($this->_exceptions as $exception) {
        $this->_content .= "   * @throws ".$exception."\n";
      }
      $this->_content .= "   */\n";
      $this->_content .= "  public function ".$this->_name."(".implode(', ', $arguments).") {\n";
      $this->_content .= "     \$this->_beforeCall(__CLASS__, __FUNCTION__, func_get_args());\n";
      $this->_content .= "     \$result = \$this->__soapCall(\"".$this->_name."\", func_get_args());\n";
      $this->_content .= "     \$this->_afterCall(__CLASS__, __FUNCTION__, \$result);\n";
      $this->_content .= "     return \$result;\n";
      $this->_content .= "  }\n";
    }
    return $this->_content;
  }
}