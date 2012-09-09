<?php
/**
 * This class represent a Complex type class
 *
 * @package     Level42
 * @subpackage  Wsdl2Magento.Model
 */
class Level42_Wsdl2Magento_Model_WSDLClass
{

  /**
   * Name for the class file
   * @var string
   */
  private $_fileName = '';

  /**
   * Name for the class
   * @var string
   */
  private $_name = '';

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
   * List of class properties (array[name] = type)
   * @var array
   */
  private $_properties = array();

  /**
   * Constructor
   *
   * @param string $fileName     Class filename
   * @param string $name         Class name
   * @param string $extends      Parent of the class
   * @param boolean $base        True if is a base class
   * @param string[] $properties List of class attributes
   */
  public function __construct($fileName, $name, $extends = null, $base = false, $properties = array())
  {
    $this->_fileName = $fileName;
    $this->_name = $name;
    $this->_extends = $extends;
    $this->_base = $base;
    $this->_properties = $properties;
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
      $this->_content .= " * Webservice object ".$this->_name."\n";
      $this->_content .= " *\n";
      $this->_content .= " * Generated ".date("Y-m-d G:i:s")."\n";
      $this->_content .= " *\n";
      $this->_content .= " * This class is automatically generated\n";
      $this->_content .= " * Do not modify or commit !\n";
      $this->_content .= " */\n";
      $this->_content .= "class ".$this->_name;
      $this->_content .= " extends ".$this->_extends;
      $this->_content .= " {\n\n";

      foreach ($this->_properties as $propertyName => $propertyType) {
        $this->_content .= "  /**\n"
        . "   * ".$propertyName." attribute\n"
        . "   * @access public\n"
        . "   * @var ".$propertyType."\n"
        . "   */\n"
        . "  ".'public $'.$propertyName.";\n\n";
      }

      foreach ($this->_properties as $propertyName => $propertyType) {
        // GETTER
        $this->_content .= "  /**\n"
        . "   * Getter for ".$propertyName." attribute\n"
        . "   * \n"
        . "   * @return ".$propertyType." : ".$propertyName."\n"
        . "   */\n"
        . "  ".'public function get'.ucfirst($propertyName)."() {\n"
        . "    return \$this->".$propertyName.";\n  }\n\n";
        // SETTER
        $this->_content .= "  /**\n"
        . "   * Setter for ".$propertyName." attribute\n"
        . "   * \n"
        . "   * @param ".$propertyType." $".$propertyName."\n"
        . "   */\n"
        . "  ".'public function set'.ucfirst($propertyName)."($".$propertyName.") {\n"
        . "    \$this->".$propertyName." = $".$propertyName.";\n  }\n\n";
      }
      $this->_content .= "}";
    }
    return $this->_content;
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
      $this->_content .= " * Webservice object ".$this->_name."\n";
      $this->_content .= " */\n";
      $this->_content .= "class ".$this->_name;
      $this->_content .= " extends ".$this->_extends." {\n";
      $this->_content .= "  /**\n";
      $this->_content .= "   * Overwrite the parent class here\n";
      $this->_content .= "   * This class is never overwrite\n";
      $this->_content .= "   */\n";
      $this->_content .= "}\n";
    }
    return $this->_content;
  }
}