<?php

/**
 * Class used to read WSDL file and analyse content
 * 
 * @package     Level42
 * @subpackage  Wsdl2Magento.Model
 */
class Level42_Wsdl2Magento_Lib_WSDLInterpreter
{
  
  /**
   * The WSDL document's URI
   * @var string
   * @access private
   */
  private $_wsdl = null;

  /**
   * The class namespace.
   * @var string
   * @access private
   */
  private $_classNamespace = null;

  /**
   * Namespace for parent class
   *
   * @var string
   */
  private $_baseNamespace = null;

  /**
   * Parent classname
   *
   * @var string
   */
  private $_parentClassname = null;

  /**
   * List of classes
   * @var Level42_Wsdl2Magento_Lib_WSDLClass[]
   */
  private $_classes = array();

  /**
   * List of services classes
   * @var Level42_Wsdl2Magento_Lib_WSDLService[]
   */
  private $_services = array();

  /**
   * Parent class for services parent class
   * @var string
   */
  private $_parentSoapClient = 'SoapClient';

  /**
   * The service namespace.
   * @var string
   * @access private
   */
  private $_serviceNamespace = null;

  /**
   * The service URI
   * @var string
   * @access private
   */
  private $_service = null;

  /**
   * DOM document representation of the wsdl and its translation
   * @var DOMDocument
   * @access private
   */
  private $_dom = null;

  /**
   * Array of classes and members representing the WSDL message types
   * @var array
   * @access private
   */
  private $_classmap = array();

  /**
   * Parses the target wsdl and loads the interpretation into object members
   * @param $wsdl            URL du WSDL à utiliser pour contruire les classes
   * @param $objectNS        Namespace des objets WSDL
   * @param $baseNS          Namespace des objets parents WSDL
   * @param $parentClassname Classe parente des objets SOAP
   *
   * @throws Level42_Wsdl2Magento_Lib_WSDLInterpreterException Container for all WSDL interpretation problems
   */
  public function __construct($wsdl, $objectNS, $baseNS, $parentClassname = null, $parentSoapClass = 'SoapClient')
  {
			try {
				$this->_wsdl = $wsdl;
				$this->_classNamespace = $objectNS;
				$this->_baseNamespace = $baseNS;
				$this->_parentClassname = $parentClassname;
				$this->_parentSoapClient = $parentSoapClass;

				$this->_dom = new DOMDocument();
				if (@$this->_dom->load($wsdl, LIBXML_DTDLOAD|LIBXML_DTDATTR|LIBXML_NOENT|LIBXML_XINCLUDE) === false) {
					throw new Level42_Wsdl2Magento_Lib_WSDLInterpreterException("WSDL document ".$wsdl." is unavailable.");
				}
				
				$xpath = new DOMXPath($this->_dom);
				$results = $xpath->query("//@targetNamespace");
				$this->_serviceNamespace = $results->item(0)->value;

				/**
				 * wsdl:import
				 */
				$query = "//*[local-name()='import' and namespace-uri()='http://schemas.xmlsoap.org/wsdl/']";
				$entries = $xpath->query($query);
				foreach ($entries as $entry) {
					$parent = $entry->parentNode;
					$wsdl = new DOMDocument();
					$wsdl->load($entry->getAttribute("location"), LIBXML_DTDLOAD|LIBXML_DTDATTR|LIBXML_NOENT|LIBXML_XINCLUDE);
					foreach ($wsdl->documentElement->childNodes as $node) {
						$newNode = $this->_dom->importNode($node, true);
						$parent->insertBefore($newNode, $entry);
					}
					$parent->removeChild($entry);
				}

				/**
				 * xsd:import
				 */
				$query = "//*[local-name()='import' and namespace-uri()='http://www.w3.org/2001/XMLSchema']";
				$entries = $xpath->query($query);
				foreach ($entries as $entry) {
					$parent = $entry->parentNode;
					$xsd = new DOMDocument();
					$result = @$xsd->load(dirname($this->_wsdl) . "/" . $entry->getAttribute("schemaLocation"),
							LIBXML_DTDLOAD|LIBXML_DTDATTR|LIBXML_NOENT|LIBXML_XINCLUDE);
					if ($result) {
						foreach ($xsd->documentElement->childNodes as $node) {
							$newNode = $this->_dom->importNode($node, true);
							$parent->insertBefore($newNode, $entry);
						}
						$parent->removeChild($entry);
					}
				}

				$this->_dom->formatOutput = true;
			} catch (Exception $e) {
				throw new Level42_Wsdl2Magento_Lib_WSDLInterpreterException("Error loading WSDL document (".$e->getMessage().")");
			}

			try {
				$xsl = new XSLTProcessor();
				$xslDom = new DOMDocument();
				$xslDom->load(dirname(__FILE__)."/../etc/wsdl2php.xsl");
				$xsl->registerPHPFunctions();
				$xsl->importStyleSheet($xslDom);
				$this->_dom = $xsl->transformToDoc($this->_dom);
				$this->_dom->formatOutput = true;
			} catch (Exception $e) {
				throw new Level42_Wsdl2Magento_Lib_WSDLInterpreterException("Error interpreting WSDL document (".$e->getMessage().")");
			}

			$this->_loadClasses();
			$this->_loadServices();
  }

  /**
   * Validates a name against standard PHP naming conventions
   *
   * @param string $name      The name to validate
   * @param boolean $camelize Camelize the name
   *
   * @return string the validated version of the submitted name
   *
   * @access private
   */
  private function _validateNamingConvention($name, $camelize = false)
  {
    $value = preg_replace('#[^a-zA-Z0-9_\x7f-\xff]*#', '',
        preg_replace('#^[^a-zA-Z_\x7f-\xff]*#', '', $name));

    return $camelize ? $this->_camelize($value) : $value;
  }


  /**
   * Camelize a class name
   *
   * @param string $name the name to camelize
   *
   * @return string Classname camelized
   */
  private function _camelize($name)
  {
    return ucfirst($name);
  }

  /**
   * Validates a class name against PHP naming conventions and already defined
   * classes, and optionally stores the class as a member of the interpreted classmap.
   *
   * @param string $className Nom de la classe
   * @param boolean $addToClassMap Ajout dans le classmap
   * @param boolean $base Est une classe de base
   * @param boolean $ns Doit possèder un namespace
   *
   * @return string the validated version of the submitted class name
   *
   * @access private
   */
  private function _validateClassName($className, $base = false)
  {
    $validClassName = $this->_validateNamingConvention($className, true);

    // Ajout du classnamespace
    if ($base) {
      $validClassName = $this->_baseNamespace.$validClassName;
    } else {
      $validClassName = $this->_classNamespace.$validClassName;
      $this->_classmap[$className] = $validClassName;
    }

    return $validClassName;
  }


  /**
   * Validates a wsdl type against known PHP primitive types, or otherwise
   * validates the namespace of the type to PHP naming conventions
   *
   * @param string $type the type to test
   *
   * @return string the validated version of the submitted type
   *
   * @access private
   * @todo Extend type handling to gracefully manage extendability of wsdl definitions, add reserved keyword checking
   */
  private function _validateType($type)
  {
    $integers = array("int", "integer", "long", "byte", "short", "negativeinteger", "nonnegativeinteger", "nonpositiveinteger",
        "positiventeger", "unsignedbyte", "unsignedint", "unsignedlong", "unsignedshort");

    $doubles = array("float", "long", "double", "decimal");

    $strings = array("string", "token", "normalizedstring", "hexbinary");

    $boolean = array("boolean", "bool");

    $array = false;
    if (substr($type, -2) == "[]") {
      $array = true;
      $type = substr($type, 0, -2);
    }
    if (substr($type, 0, 7) == "ArrayOf") {
      $array = true;
      $type = substr($type, 8);
    }

    $type = strtolower($type);


    if (array_search($type, $integers) !== false) {
      // Is a integer type
      $validType = 'int';
    } elseif (array_search($type, $doubles) !== false) {
      // Is a double type
      $validType = 'double';
    } elseif (array_search($type, $strings) !== false) {
      // Is s string type ?
      $validType = 'string';
    } elseif (array_search($type, $boolean) !== false) {
      // Is a boolean type ?
      $validType = 'boolean';
    } else {
      // Other type (ComplexType)
      $validType = $this->_validateNamingConvention($type);
      $validType = $this->_camelize($validType);
      $validType = $this->_classNamespace.$validType;
    }

    if ($array) {
      $validType .= "[]";
    }
    return $validType;
  }

  /**
   * Loads classes from the translated wsdl document's message types
   *
   * @access private
   */
  private function _loadClasses()
  {
    $classes = $this->_dom->getElementsByTagName("class");

    foreach ($classes as $class) {
      $extends = $class->getElementsByTagName("extends");
      $classExtension = null;
      if ($extends->length > 0) {
        $extends->item(0)->nodeValue = $this->_validateClassName($extends->item(0)->nodeValue, true);
        $classExtension = $extends->item(0)->nodeValue;
      }

      // Set the extends class
      $extends = ($classExtension != null ? $classExtension : $this->_parentClassname );

      // propriété de la classe
      $properties = array();
      foreach ($class->getElementsByTagName("entry") as $property) {
        $propName = $this->_validateNamingConvention($property->getAttribute("name"));
        $proType = $this->_validateType($property->getAttribute("type"));
        $properties[$propName] = $proType;
      }

      $filename = $this->_validateNamingConvention($class->getAttribute("name"), true);
      $name = $this->_validateClassName($class->getAttribute("name"), false);
      $baseName = $this->_validateClassName($class->getAttribute("name"), true);

      $childClass = new Level42_Wsdl2Magento_Model_WSDLClass(
          $filename,
          $name,
          $baseName,
          false);

      $baseClass = new Level42_Wsdl2Magento_Model_WSDLClass(
          $filename,
          $baseName,
          $this->_parentClassname,
          true,
          $properties);
       
      $this->_classes[$name] = $childClass;
      $this->_classes[$baseName] = $baseClass;
    }
  }

  /**
   * Loads services from the translated wsdl document
   *
   * @access private
   */
  private function _loadServices()
  {
    $services = $this->_dom->getElementsByTagName("service");
    foreach ($services as $service) {
      $filename = $this->_validateNamingConvention($service->getAttribute("name"), true);
      $name = $this->_validateClassName($service->getAttribute("name"));
      $baseName = $this->_validateClassName($service->getAttribute("name"), true);

      $childClass = new Level42_Wsdl2Magento_Model_WSDLService(
          $filename,
          $name,
          $baseName,
          array(),
          false);


      $serviceFunctions = array();

      $functions = $service->getElementsByTagName("function");
      foreach ($functions as $function) {
        /* @var $function DomElement */
        $name = $this->_validateNamingConvention($function->getAttribute("name"));

        $serviceFunction = new Level42_Wsdl2Magento_Model_WSDLServiceFunction($name);

        // List function arguments
        $parameters = $function->getElementsByTagName("parameters");
        if ($parameters->length > 0) {
          $parameterList = $parameters->item(0)->getElementsByTagName("entry");
          foreach ($parameterList as $variable) {
            $paramName = $this->_validateNamingConvention($variable->getAttribute("name"));
            $paramType = $this->_validateType($variable->getAttribute("type"));
            $serviceFunction->addParameter($paramName, $paramType);
          }
        }

        // List function returntype
        $returns = $function->getElementsByTagName("returns");
        if ($returns->length > 0) {
          $returns = $returns->item(0)->getElementsByTagName("entry");
          if ($returns->length > 0) {
            $serviceFunction->setReturnType($this->_validateType($returns->item(0)->getAttribute("type")));
          }
        }

        // List function exception
        $exceptions = $function->getElementsByTagName("exceptions");
        if ($exceptions->length > 0) {
          $exceptions = $exceptions->item(0)->getElementsByTagName("entry");
          if ($exceptions->length > 0) {
            $serviceFunction->addException($exceptions->item(0)->getAttribute("type"));
          }
        }

        $serviceFunctions[$name] = $serviceFunction;
      }


      $baseClass = new Level42_Wsdl2Magento_Model_WSDLService(
          $filename,
          $baseName,
          $this->_parentSoapClient,
          $serviceFunctions,
          true,
          $this->_wsdl,
          $this->_service,
          $this->_serviceNamespace,
          $this->_classmap);

      $this->_services[$name] = $childClass;
      $this->_services[$baseName] = $baseClass;
    }
  }

  /**
   * Saves the PHP source code that has been loaded to a target directory.
   *
   * Services will be saved by their validated name, and classes will be included
   * with each service file so that they can be utilized independently.
   *
   * @return array array of source code files that were written out
   * @throws Level42_Wsdl2Magento_Lib_WSDLInterpreterException problem in writing out service sources
   * @access public
   * @todo Add split file options for more efficient output
   */
  public function savePHPObjects($outputDirectory, $baseOutputDirectory)
  {
    foreach ($this->_classes as $classCode) {
      /* @var $classCode Level42_Wsdl2Magento_Lib_WSDLClass */
      if ($classCode->isBaseClass()) {
        $classCode->output($baseOutputDirectory);
      } else {
        $classCode->output($outputDirectory);
      }
    }
  }

  /**
   * Saves the PHP source code that has been loaded to a target directory.
   *
   * Services will be saved by their validated name, and classes will be included
   * with each service file so that they can be utilized independently.
   *
   * @param string $outputDirectory the destination directory for the source code
   * @return array array of source code files that were written out
   * @throws Level42_Wsdl2Magento_Lib_WSDLInterpreterException problem in writing out service sources
   * @access public
   * @todo Add split file options for more efficient output
   */
  public function savePHPServices($outputDirectory, $baseOutputDirectory)
  {
    foreach ($this->_services as $classCode) {
      /* @var $classCode Level42_Wsdl2Magento_Lib_WSDLService */
      if ($classCode->isBaseClass()) {
        $classCode->output($baseOutputDirectory);
      } else {
        $classCode->output($outputDirectory);
      }
    }
  }
}
