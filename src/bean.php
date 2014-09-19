<?php
namespace Xily;

/**
 * The Bean class extends the Xily\Xml class and allows advanced parsing features for XML data.
 * Based on the Bean class you can create your own
 * micro parsers (Beans) which are stored in the /beans directory of your
 * xily installation.
 * One more speciality about Beans:
 * Although you may define your own Beans in PHP and create your
 * own logic and functions arround them, Beans hold very powerful
 * data referencing methods, calls XDR - Xily Data References.
 * An XDR helps you to use data stored in object OUTSIDE the tag you
 * are using.
 *
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 2.0 (2013-11-13)
 * @package Xily
 * @copyright Copyright (c) 2009-2013, Peter-Christoph Haider
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class Bean extends Xml {
	/** @var string|bool|array|object The container of the object's results. A result can be of varible types. */
	private $mxtResult;
	/** @var bool This switch commands the run() function to respect or ignore the source attribute of the tag. */
	private $bolLoadFromURL = true;
	/** @var bool This switch commands the result() function to return the XML-string of the node or to ignore it in the result collection. */
	public $bolReturnXML = true;
	/** @var array Container for all utility data sets */
	private $arrDataset = array();
	/** @var array Container for all object links */
	private $arrLink = array();
	/** @var array Container for all heritage objects to be refused */
	private $arrNoHeritage = array('attribute' => array(), 'value' => array(), 'link' => array(), 'dataset' => array(), 'root' => array(), 'parent' => array());
	/** @var array List of additional bean directories  */
	public static $BEAN_DIRS = array();
	/** @var string The base path for external includes */
	public static $basepath = '';

	/**
	 * Loads the XML data from a string or file
	 *
	 * @param string $strXML Name of the XML file or XML string
	 * @param bool $bolLoadFile Load from file or parse locally
	 * @return Bean
	 */
	public static function create($strXML='', $bolLoadFile=false) {
		return $strXML == '' ? new Bean() : self::returnNode($strXML, new Bean(), $bolLoadFile);
	}

	/**
	 * Scan the bean directories and include the Bean
	 * @param array $addDirs
	 * @param string $strParserFile
	 * @param string $strParserClass
	 */
	private function includeNode($addDirs, $strParserFile, $strParserClass) {
		if (class_exists('\Xily\\'.$strParserClass))
			return '\Xily\\'.$strParserClass;
		foreach ($addDirs as $strDir) {
			if (file_exists($this->fileFormatDir($strDir).$strParserFile)) {
				include_once($this->fileFormatDir($strDir).$strParserFile);
				if (class_exists('\Xily\\'.$strParserClass)) {
					return '\Xily\\'.$strParserClass;
				}
			}
		}
		return false;
	}

	/**
	 * Creates a Bean node
	 * The function will check, if there is a specific Bean class in the beans/ directory and include it, if availiable
	 * @param string $strTag
	 * @param array $arrAttributes
	 * @param Bean $xmlParent
	 * @return Bean
	 */
	public function createNode($strTag='', $arrAttributes=array(), $xmlParent=null) {
		$arrParserKey = explode(":", $strTag);

		$addDirs = self::$BEAN_DIRS ? self::$BEAN_DIRS : array(dirname(__FILE__).DIRECTORY_SEPARATOR.'beans');

		if (isset($arrParserKey[1])) {
			if ($strParserClass = $this->includeNode($addDirs, $arrParserKey[0].DIRECTORY_SEPARATOR.$arrParserKey[1].'.php', ucfirst($arrParserKey[0]).ucfirst($arrParserKey[1]))) {
				return new $strParserClass($strTag, null, $arrAttributes, $xmlParent);
			}
		}

		if ($strParserClass = $this->includeNode($addDirs, $arrParserKey[0].'.php', 'Bean'.ucfirst($arrParserKey[0])))
			return new $strParserClass($strTag, null, $arrAttributes, $xmlParent);

		return new Bean($strTag, null, $arrAttributes, $xmlParent);
	}

	// =============  Execution functions  =============

	/**
	 * Nothing to do here besides parsing the sub structure of the node deviations of Xily\Xml
	 * e.g. Beans, may have different build functions.
	 *
	 * @return void
	 */
	public function build() {
		$this->initDatasets();
		$this->heritage();
	}

	/**
	 * Returns a dump of the nodes content
	 *
	 * @param mixed $mxtData Temporary dataset [n:data]
	 * @return string The content of the node including CDATA and subordinate bean results
	 * @category iXML/Wrap
	 */
	public function dump($mxtData=null) {
		$arrContent = $this->content();
		$strResult = '';
		if ($this->hasContent()) {
			foreach ($arrContent as $content) {
				if (is_string($content))
					$strResult .= $this->xdrInsert($content, $mxtData, 1);
				if ($content instanceof Bean)
					$strResult .= $content->run($mxtData);
			}
		}
		return $strResult;
	}

	/**
	 * Executes all functions included in the Bean
	 * First, all datasets will be loaded and Xily Data References (XDR) included
	 * Then the bean's result() function will be called
	 *
	 * @param mixed $mxtData Temporary dataset [n:data]
	 * @param int $intLevel Level within the execution hierarchie
	 * @return string|mixed Result of the run (usually a string, e.g. HTML code)
	 * @category iXML/Wrap
	 */
	public function run($mxtData=null, $intLevel=0) {
		// If the object has a _source meta attribute, load the children from a local file.
		if ($this->hasAttribute('_source')) {
			$xlySource = $this->xdr($this->attribute('_source'), $mxtData, 0, 0);
			if ($xlySource instanceof Bean) {
				$this->arrAttributes = array_merge($this->arrAttributes, $xlySource->attributes());
				$this->arrChildren = $xlySource->children();
				$this->arrCDATA = $xlySource->cdata();
				$this->arrContent = $xlySource->arrContent;
				// Inherit the root and parent object...
				// $this->inherit('root', 0, 0, 1);
				// $this->inherit('parent');
				$this->heritage();
			}
			if (is_string($xlySource))
				$this->setValue($xlySource);
			unset($this->arrAttributes['_source']);
		}
		// Return the results of the tag.
		return $this->result($mxtData, $intLevel);
	}

	/**
	 * Executes the run() function on all child nodes
	 *
	 * @param mixed $mxtData Temporary dataset [n:data]
	 * @param int $intLevel Level within the execution hierarchie
	 * @param bool $bolArray If active, all results will be written to an array
	 * @return string|array|bool Result of the run
	 * @category iXML/Wrap
	 */
	public function runChildren($mxtData=null, $intLevel=0, $bolArray=0) {
		$arrResult = array();

		// Get the children's result - and check if the result is an array or a string
		foreach ($this->children() as $xmlChild)
			$arrResult[] = $xmlChild->run($mxtData, $intLevel);

		return $arrResult;
	}

	/**
	 * Works like the tag() method, but also applies the xdrInsert() function first.
	 * If $bolReturnXML = true, it will return the XML string of the object and will then
	 * execute the runChildren() function to get it's children's results.
	 *
	 * @param mixed $mxtData Temporary dataset
	 * @param int $intLevel Level within the execution hierarchie
	 * @return string|mixed Result of the run (usually a string, e.g. HTML code)
	 */
	public function result($mxtData, $intLevel=0) {
		if ($this->bolReturnXML) {
			$strTab = str_repeat("\t", $intLevel);
			$strResult = $strTab."<".$this->strTag;
			// Build the tag
			if ($this->hasAttributes()) {
				foreach ($this->arrAttributes as $key => $value)
					$strResult .= ' '.$key.'="'.$this->xdrInsert($value, $mxtData).'"';
			}

			if ($this->hasValue() || $this->hasCdata()) {
				$strResult .= '>';
				$arrContent = $this->content();
				foreach ($arrContent as $content) {
					if (is_string($content))
						$strResult .= $this->xdrInsert($content, $mxtData);
					elseif ($content instanceof Bean)
						$strResult .= $content->run($mxtData);
					else
						$strResult .= $content;
				}
				$strResult .= '</'.$this->strTag.'>';
			} elseif ($this->hasChildren()) {
				$strResult .= ">\n".implode("\n", $this->runChildren($mxtData, $intLevel+1))."\n".$strTab.'</'.$this->strTag.'>';
			} else {
				$strResult .= in_array($this->tag(), self::$OPEN_TAGS) ? '></'.$this->tag().'>' : '/>';
			}
			return $strResult;
		} else {
			return implode("\n", $this->runChildren($mxtData, $intLevel));
		}
	}

	// =============  XDR functions  =============

	/**
	 * This function evaluates and inserts multiple XDRs into a string.
	 *
	 * @param string $strXDR The string containing the XDRs or an XDR itself
	 * @param string|array|Xml $mxtData Temporary dataset
	 * @param bool $bolStrict If strict mode is of, the function will try to convert unfitting object to strings (e.g. XML files)
	 * @param bool $bolReturnEmpty If true (default) the function will replace and invalid XDR with an empty string
	 * @return string|array|Xml
	 */
	public function xdrInsert($strXDR, $mxtData="", $bolStrict=false, $bolReturnEmpty=true) {
		preg_match_all('/#\{(.*)\}/U', $strXDR, $arrXDRs);
		if (sizeof($arrXDRs[1]) > 0)
			foreach ($arrXDRs[1] as $strSubXDR) {
				$strResult = $this->xdr($strSubXDR, $mxtData, 1, $bolStrict);
				if (is_string($strResult) || is_numeric($strResult))
					$strXDR = preg_replace('/#\{'.preg_quote($strSubXDR, '/').'}/', $strResult, $strXDR);
				elseif ($bolReturnEmpty)
					$strXDR = preg_replace('/#\{'.preg_quote($strSubXDR, '/').'}/', '', $strXDR);
			}

			return $strXDR;
	}

	/**
	 * Evalutates a Xily Data Reference (XDR)
	 * The function covers the following XDR shapes in this order:
	 *
	 * {_request(var):type} (Request variables: _post, _get, _request)
	 * {_request(var):type:default}
	 * {object::dataset}
	 * {external:type:datapath}
	 * {external:type}
	 * {object:dataset:datapath}
	 * {object:datapath}
	 * {.objectpath}
	 * {datapath}
	 *
	 * @param string $strXDR The XDR string
	 * @param string|array|Xml $mxtData Temporary dataset
	 * @param bool $bolStringOnly If true, the function will only return results of type string
	 * @param bool $bolStrict If strict mode is off, the function will try to convert unfitting object to strings (e.g. XML files)
	 * @return string|array|Xml|mixed
	 */
	public function xdr($strXDR, $mxtData="", $bolStringOnly=true, $bolStrict=false) {
		// First, remove all brackets from the Object
		$strXDR = $this->strStripString($strXDR, '#{', '}', 1);
		// Then recursively insert
		// TESTING: $this->probe("xdr", "%%% New XDR: ".$strXDR." %%%", 0, 1);
		// TESTING: $this->probe("xdr", "Only Strings: ".($bolStringOnly?'on':'off'));
		// TESTING: $this->probe("xdr", "XDR: ".$strXDR, 1);

		// Inserting subordinate XDRs
		$strXDR = $this->xdrInsert($strXDR, $mxtData, 1);
		// TESTING: $this->probe("xdr", ">> XDR completely inserted: ".$strXDR, 0, 1);

		// Identify the XDR class for each XDR found in the string
		// Split the string by the separator "::"
		// If this exists, the XDR refers to a whole dataset: {object::dataset}
		// OLD: if (preg_match_all('/((?:[^:]+\(.*\))|(?:[^:]+))::((?:[^:]+\(.*\))|(?:[^:]+))/', $strXDR, $arrXDR, PREG_SET_ORDER)) {
		if (preg_match_all('/(.*)::(.*)/', $strXDR, $arrXDR, PREG_SET_ORDER)) {
			// -----> CASE: {object::dataset}
			// TESTING: $this->probe("xdr", "CASE: {object::dataset}", 2);
			$arrXDR = array_pop($arrXDR);
			if ($mxtObject = $this->xdrRetriveObject($arrXDR[1])) {
				$mxtDataset = $mxtObject->dataset($arrXDR[2]);
				if (is_null($mxtDataset)) {
					if (!$bolStrict)
						return '';
					throw new Exception('The required dataset "'.$arrXDR[2].'" cannot be supplied by the object.');
				} else {
					if ($bolStringOnly && !$bolStrict) {
						if (is_array($mxtDataset)) {
							return var_export($mxtDataset, 1);
						} elseif ($mxtDataset instanceof Xml) {
							return $mxtDataset->toString();
						} elseif (is_string($mxtDataset))
							return $mxtDataset;
					} else
						return $mxtDataset;
				}
			}
		} elseif (preg_match_all('/^(%[a-z]+)(?:->(.*))?$/i', $strXDR, $arrBase, PREG_SET_ORDER)) {
			// -----> CASE: {%var}, {%var->path}
			$arrBase = array_pop($arrBase);
			array_shift($arrBase);
			$arrVar = $this->getPredefinedVar(array_shift($arrBase));
			$strPath = array_pop($arrBase);
			if ($strPath == null)
				return $arrVar;

			$dict = new Dict($arrVar);
			// @todo: Implement filter queries for Xily\Dict
			// return $dict->getFromPath($strPath);
		} elseif (preg_match_all('/^.(open|post|get)\(((?:\"(.*)\")|(?:\'(.*)\')|(.*))\)((?:->(.*)->(.*))|(?:->(.*)))?$/i', $strXDR, $arrBase, PREG_SET_ORDER)) {
			// -----> CASE: {.external}, {.external->type}, {.external->type->path}
			$arrBase = array_pop($arrBase);

			array_shift($arrBase); // Entire path
			$strMethod = array_shift($arrBase); // Method (open|post|get)
			$strURL    = array_shift($arrBase);
			$strFormat = null;
			if ($strURL == null || $strURL == '')
				throw new Exception('No request URL specified in '.$strXDR);

			if (isset($arrBase[2]) && $arrBase[2] != '')  // Unquoted URL
				$strURL = $arrBase[2];
			if (isset($arrBase[1]) && $arrBase[1] != '')  // Single-quoted URL
				$strURL = $arrBase[1];
			if (isset($arrBase[0]) && $arrBase[0] != '')  // Double-quoted URL
				$strURL = $arrBase[0];

			if (isset($arrBase[6]) && $arrBase[6] != '')
				$strFormat = $arrBase[6]; // CASE: {.external->type}
			elseif (isset($arrBase[5]) && $arrBase[5] != '') {
				$strPath = $arrBase[5];
				if (isset($arrBase[4]) && $arrBase[4] != '')
					$strFormat = $arrBase[4];
				else
					$strFormat = 'xml'; // Default format, e.g. in case of {.external->->path}
			}

			$mxtExternalData = $this->xdrRetriveExternal($strURL, $strFormat, $strMethod);
			return isset($strPath) ? $this->xdrRetriveData($strPath, $mxtExternalData, $bolStringOnly) : $mxtExternalData;
		} elseif (preg_match_all('/^(((.*)->(.*)->(.*))|((.*)->(.*))|(.*))$/', $strXDR, $arrBase, PREG_SET_ORDER)) {
			// -----> CASE: ALL local nodes
			$arrBase = array_pop($arrBase);

			if (isset($arrBase[9]) && $arrBase[9] != '') {   // {datapath} or {.objectpath}
				$strNode = $arrBase[9];
				if (substr($strNode, 0, 1) == '.')
					return $this->xdrRetriveObject($strNode, $bolStringOnly, $bolStrict);
				else
					return $this->xdrRetriveData($strNode, $mxtData ? $mxtData : $this->dataset(), $bolStringOnly);
			} elseif (isset($arrBase[8]) && $arrBase[8] != '') { // {objectpath->datapath}
				$strNode    = $arrBase[7];
				$strPath    = $arrBase[8];
				$strDataset = null;
			} elseif (isset($arrBase[5]) && $arrBase[5] != '') { // {objectpath->dataset->datapath}
				$strPath    = $arrBase[5];
				$strDataset = $arrBase[4];
				$strNode    = $arrBase[3];
			} else {
				throw new Exception('Invalid XDR statement: '.$strXDR);
			}

			if ($mxtObject = $this->xdrRetriveObject($strNode))
				return $this->xdrRetriveData($strPath, $mxtObject->dataset($strDataset), $bolStringOnly);

			throw new Exception('Could not resolve object path: '.$strNode);
		}

		throw new Exception('Invalid XDR statement: '.$strXDR);
	}

	/**
	 * Retrieves a local object
	 * This function evaluates an XML path, in order to receive an XML object or to trace the complete path ($bolStringOnly).
	 *
	 * @param string $strObject The object path or ID of the node
	 * @param bool $bolStringOnly If true, the function will only return results of type string
	 * @param bool $bolStrict If strict mode is of, the function will try to convert unfitting object to strings (e.g. XML files)
	 * @return string|array|Xml|mixed
	 */
	private function xdrRetriveObject($strObject, $bolStringOnly=false, $bolStrict=false) {
		// TESTING: $this->probe('xdrRetriveObject', "STEP1: Retrieving Object: ".$strObject, 4);
		// TESTING: $this->probe('xdrRetriveObject', "Only Strings: ".($bolStringOnly?'on':'off'), 4);

		// Get the root object of the current tag. We might need it to reference other objects
		if (!$xmlRoot = $this->root())
			throw new Exception('Could not process XDR: No root object set for node.');

		// Analyze the object path and check for special cases (.root, .parent, .this)
		$arrObject = explode('.', $strObject);
		if (sizeof($arrObject) > 1) {
			// Check, if the objectpath starts with a '.'
			if ($arrObject[0] == '') {
				// TESTING: $this->probe('xdrRetriveObject', "Special object root or object path detected", 4);
				array_shift($arrObject);
				if (strtolower($arrObject[0]) == 'root') {
					// TESTING: $this->probe('xdrRetriveObject', "Load Object: .root", 5);
					$mxtObject = $xmlRoot;
				} elseif (strtolower($arrObject[0]) == 'parent') {
					// TESTING: $this->probe('xdrRetriveObject', "Load Object: .parent", 5);

					if (!$mxtObject = $this->parent())
						throw new Exception('Could not process XDR: The node has no parent. (poor thing...)');

					// TESTING: $this->probe('xdrRetriveObject', "Object .parent successfully loaded", 5);
					$arrObject = array_slice($arrObject, 1);
				} elseif (strtolower($arrObject[0]) == 'this') {
					// TESTING: $this->probe('xdrRetriveObject', "Load Object: .this", 5);
					$mxtObject = $this;
				}
			}
		}
		// Now make sure that there is a reference object available by now
		if (!isset($mxtObject)) {
			// TESTING: $this->probe('xdrRetriveObject', "Loading Object: ".$arrObject[0], 6);
			if (!$mxtObject = $xmlRoot->getNodeById($arrObject[0])) {
				// TESTING: $this->probe('xdrRetriveObject', "Error loading the object \"$arrObject[0]\"", 6);
				return false;
			}
		}
		// TESTING: $this->probe('xdrRetriveObject', "Object successfully loaded; Object type '".$mxtObject->tag()."'", 6);
		// Shorten the objectpath by the first element
		array_shift($arrObject);
		// Evaluating the object's path
		$strObjectpath = implode('.', $arrObject);

		if ($bolStringOnly) {
			if ($strObjectpath == '') {
				// TESTING: $this->probe('xdrRetriveObject', "No Objectpath detected. Returning the object's value.", 7);
				return $mxtObject->dump();
			} else {
				// TESTING: $this->probe('xdrRetriveObject', "Tracing the objectpath now: ".$strObjectpath, 7);
				return $mxtObject->trace($strObjectpath);
			}
		} else {
			if ($strObjectpath == '')
				return $mxtObject;
			else {
				// TESTING: $this->probe('xdrRetriveObject', "Evaluate the rest of the object path: ".$strObjectpath, 7);
				$xlsObject = $mxtObject->getNodesByPath($strObjectpath);
				switch (sizeof($xlsObject)) {
					case 0:
						// TESTING: $this->probe('xdrRetriveObject', 'Could not process XDR: The node "'.$arrObject[0].'" could not process the path.');
						return false;
					case 1:
						// TESTING: $this->probe('xdrRetriveObject', "Object retrieved - returning a single object", 7);
						return $xlsObject[0];
					default:
						// TESTING: $this->probe('xdrRetriveObject', "Object list retrieved - returning the complete list", 7);
						return $xlsObject;
				}
			}
		}
	}

	/**
	 * This function evaluates a datapath relativ to the given dataset.
	 *
	 * @param string $strDataPath The XML data path
	 * @param Xml $mxtDataset The dataset on which the datapath should be applied
	 * @param bool $bolStringOnly If true, the function will only return results of type string
	 * @param bool $bolStrict If strict mode is of, the function will try to convert unfitting object to strings (e.g. XML files)
	 * @return string|array|Xml|mixed
	 */
	private function xdrRetriveData($strDataPath, $mxtDataset, $bolStringOnly=true, $bolStrict=false) {
		// TESTING: $this->probe('xdrRetriveData', "STEP3: Evaluating datapath", 7);
		// TESTING: $this->probe('xdrRetriveData', "Only Strings: ".($bolStringOnly?'on':'off'), 7);
		// TESTING: $this->probe('xdrRetriveData', "Path: ".$strDataPath, 7);
		if (is_array($mxtDataset)) {
			// TESTING: $this->probe('xdrRetriveData', "Dataset is an array", 8);
			$xlyArray = new Dict($mxtDataset);

			return $xlyArray->get($strDataPath, false);
		} elseif ($mxtDataset instanceof Xml || $mxtDataset instanceof Bean) {
			// TESTING: $this->probe('xdrRetriveData', "Dataset is an XML/Bean document", 8);
			if ($bolStringOnly) {
				// TESTING: $this->probe('xdrRetriveData', "Tracing the datapath now: ".$strDataPath, 9);
				return $mxtDataset->trace($strDataPath);
			} else {
				// TESTING: $this->probe('xdrRetriveData', "Retrieving the object now: ".$strDataPath, 9);
				$mxtData = $mxtDataset->getNodesByPath($strDataPath);
				if (isset($mxtData[1]))
					return $mxtData;
				elseif (isset($mxtData[0]))
					return $mxtData[0];
				else
					return false;
			}
		} else {
			// TESTING: $this->probe('xdrRetriveData', "Invalid dataset: Dataset must be an array or XML/Bean object.", 8);
			return false;
		}
	}

	/**
	 * Fetches an external dataset.
	 *
	 * @param string $strURL The URL or directory of the file
	 * @param string $strType Declares the type of data (plain*, xml, bean, json)
	 * @param string $strMethod Loading method for the external resource: open*, get, post
	 * @return object|string
	 */
	private function xdrRetriveExternal($strURL, $strType='plain', $strMethod='open') {
		// TESTING: $this->probe('xdrRetriveExternal', 'Trying to load the external resource now: '.$strURL.'; Method: '.$strMethod.'; Type: '.$strType, 4);

		$strData = null;
		$strMethod = strtolower($strMethod);
		switch ($strMethod) {
			case 'get':
				if (class_exists('\REST\Client')) {
					$req = new \REST\Client($strURL);
					$strData = $req->post();
				} else {
					// If the REST library is not present, simply use file_get_contents
					$strData = file_get_contents($strURL);
				}
				break;
			case 'post':
				if (!class_exists('\REST\Client'))
					throw new Exception('Class REST\\Client not found. Please include the library from https://github.com/zeyon/rest');

				$req = new \REST\Client($strURL);
				$strData = $req->post();
				break;
			default:
				$strData = is_readable(self::$basepath.$strURL) ? file_get_contents(self::$basepath.$strURL) : false;
				break;
		}

		if (!$strData)
			return;

		if ($strType == 'xml') {
			return Xml::create($strData);
		} elseif ($strType == 'bean') {
			return Bean::create($strData);
		} elseif ($strType == 'json') {
			return json_decode($strData, 1);
		} else
			return $strData;
	}

	/**
	 * Returns a predefined PHP variable
	 *
	 * @param  string $strVariable Variable name
	 * @return array
	 */
	private function getPredefinedVar($strVariable) {
		switch(strtolower($strVariable)) {
			case '%server':
				return $_SERVER;
			case '%post':
				return $_POST;
			case '%get':
				return $_GET;
			case '%files':
				return $_FILES;
			case '%request':
				return $_REQUEST;
			case '%session':
				return $_SESSION;
			case '%env':
				return $_ENV;
			case '%cookie':
				return $_COOKIE;
			case '%http':
				return file_get_contents("php://input");
			default:
				throw new Exception('Unknown variable name: '.$strVariable);

		}
	}

	// =============  Data handling functions  =============

	/**
	 * Initialize the bean's datasets (used in build() functions)
	 * A dataset is identified by a '_' as an attribute's first character
	 * e.g. <tag _dataset="{XDR}" attribute="STIRNG" />
	 *
	 * @return void
	 */
	public function initDatasets() {
		foreach ($this->arrAttributes as $key => $value) {
			if (substr($key, 0, 1) == '_' && $key != '_source') {
				// TESTING: $this->probe("initDatasets", "Inserting data in dataset '".substr($key, 1)."'; XDR: '".$value."'");
				$this->arrDataset[substr($key, 1)] = $this->xdr($value, '', 0, 1);
				unset($this->arrAttributes[$key]);
			}
		}
	}

	/**
	 * Sets the value for the $mxtDataset variable.
	 * Caution: existing data will be overwritten!
	 *
	 * @param string|array|Xml $mxtDataset [data]
	 * @param string $strDataset Name of the dataset. [name]
	 * @return void
	 * @category iXML
	 */
	public function setDataset($mxtDataset, $strDataset="default") {
		$this->arrDataset[$strDataset] = $mxtDataset;
	}

	/**
	 * Lists all available datasets of a function
	 *
	 * @param bool $bolShowAll If TRUE the function will also search all child nodes [0:recursive]
	 * @return string
	 * @category iXML
	 */
	public function showDatasets($bolShowAll=false) {
		$strResult = "";
		if (sizeof($this->arrDataset) > 0) {
			$strObject  = "";
			$strObject .= $this->strTag." [".$this->intIndex."]";
			if ($this->strID)
				$strObject .= " - (".$this->strID.")";
			foreach ($this->arrDataset as $key => $value) {
				$strResult .= $strObject."->".$key." (".gettype($value).")\n";
			}
		}
		if ($bolShowAll && $this->hasChildren())
			foreach ($this->children() as $xmlChild)
				$strResult .= $xmlChild->showDatasets(1);
		return $strResult;
	}

	/**
	 * Returns a specified dataset
	 *
	 * @param string $strDataset Name of the dataset. [name]
	 * @return string|array|Xml
	 * @category iXML
	 */
	public function dataset($strDataset="default") {
		if (array_key_exists($strDataset, $this->arrDataset))
			return $this->arrDataset[$strDataset];
		else
			return null;
	}

	/**
	 * Clears a defined dataset.
	 * If no dataset name is supplied, all datasets will be removed by clearing the entire dataset variable.
	 *
	 * @param string $strDataset Name of the dataset [n:name]
	 * @return void
	 * @category iXML
	 */
	public function clearDataset($strDataset=null) {
		if (is_null($strDataset))
			unset($this->arrDataset[$strDataset]);
		else
			$this->arrDataset = array();
	}

	/**
	 * Removes a single dataset from the $mxtDataset variable.
	 *
	 * @param string $strDataset Name of the dataset. [name]
	 * @return void
	 * @category iXML
	 */
	public function removeDataset($strDataset="default") {
		if (isset($this->arrDataset[$strDataset]))
			unset($this->arrDataset[$strDataset]);
	}

	/**
	 * Checks, if the node has a specified dataset
	 *
	 * @param string $strDataset Name of the dataset. [name]
	 * @return bool
	 * @category iXML/Wrap
	 */
	public function hasDataset($strDataset) {
		return isset($this->arrDataset[$strDataset]);
	}

	// =============  Linking and collection functions  =============

	/**
	 * Sets a link to another Bean
	 *
	 * @param string $strLink
	 * @param Bean $xlyObject
	 * @return void
	 */
	public function setLink($strLink, $xlyObject) {
		$this->arrLink[$strLink] = $xlyObject;
	}

	/**
	 * Clears all links from the node
	 *
	 * @return void
	 */
	public function clearLinks() {
		$this->arrLink = array();
	}

	/**
	 * Removes a specified link
	 *
	 * @param string $strLink
	 * @return void
	 */
	public function removeLink($strLink) {
		unset($this->arrLink[$strLink]);
	}

	/**
	 * Returns a pointer to the linked object
	 *
	 * @param string $strLink Name of the link
	 * @return Bean
	 */
	public function link($strLink) {
		return $this->hasLink($strLink) ? $this->arrLink[$strLink] : null;
	}

	/**
	 * Check, if a specified link exists
	 *
	 * @param string $strLink Name of the link
	 * @return bool
	 */
	public function hasLink($strLink) {
		return isset($this->arrLink[$strLink]) && $this->arrLink[$strLink] instanceof Dict;
	}

	/**
	 * The collect() function passes a value to the local of foreign collector
	 *
	 * @param string $strCollector The name of the collector
	 * @param mixed $mxtContent
	 * @return void
	 */
	public function collect($strCollector, $mxtContent) {
		if ($this->hasLink($strCollector))
			$this->link($strCollector)->push($mxtContent);
		elseif ($this->root()->hasLink($strCollector))
			$this->root()->link($strCollector)->push($mxtContent);
		elseif ($this->hasParent() && $this->parent()->hasLink($strCollector))
			$this->parent()->link($strCollector)->push($mxtContent);
		else
			throw new Exception('No collector (Dictionary) found for '.$strCollector, 0);
	}

	// =============  Inheriting functions  =============

	/**
	 * The heritage function collects all inherit() and noInherit() statements for each bean.
	 * Therefore, each bean has its own heritage() function.
	 * The heritag() function is called in the run() function.
	 *
	 * @return void@return void
	 */
	public function heritage() {
		// $this->preHeritage();

		// Put your inherit() statement here
		// e.g. $this->inherit('link', $this, 'godfather');
		// or   $this->inherit('attribute', 'myattribute');
		// or   $this->inherit('value');
	}

	/**
	 * The preHeritage() function should be called at the beginning of a class's
	 * heritage() function, as it calls the children's refuseHeritage() functions,
	 * which is required to invoce the noHeritage() statements before the
	 * inhertited data is passed
	 *
	 * @return void
	 */
	public function preHeritage() {
		foreach ($this->children() as $xmlChild)
			$xmlChild->refuseHeritage();
	}

	/**
	 * The refuseHeritage() function collects all noInherit() statements for each bean.
	 * Therefore, each bean has its own noheritage() function.
	 * The noheritage() function is called by the parents preheritage function.
	 *
	 * @return void
	 */
	public function refuseHeritage() {
		// Put your inherit() statement here
		// e.g. $this->inherit('link', $this, 'godfather');
		// or   $this->inherit('attribute', 'myattribute');
		// or   $this->inherit('value');
	}

	/**
	 * Inherits a certain set of properties to all child nodes.
	 *
	 * @param mixed $mxtValue The inharitence value
	 * @param string $strAs Type of inheritence (attribute, link, dataset or value)
	 * @param string $strName The attribute/dataset/link name
	 * @param bool $bolPersistent
	 * @return void
	 */
	public function inherit($strAs, $strName="", $mxtValue="", $bolPersistent=false) {
		$arrModes = array('attribute', 'value', 'link', 'dataset');
		if (in_array($strAs, $arrModes)) {
			if (!$mxtValue) {
				if ($strAs == 'attribute') $mxtValue = $this->attribute($strName);
				if ($strAs == 'link') $mxtValue = $this->link($strName);
				if ($strAs == 'dataset') $mxtValue = $this->dataset($strName);
				if ($strAs == 'value' && !$mxtValue) $mxtValue = $this->value();
			}
			foreach ($this->children() as $xmlChild)
				$xmlChild->passHeritage($strAs, $mxtValue, $strName, $bolPersistent);
		} else
			throw new Exception('Invalid operation mode: '.$strAs.'. Use "attribute", "value", "link" or "dataset" instead.');
	}

	/**
	 * This function adds a restriction for the passHeritage() function in order to refuse a certain heritage.
	 * This is especially useful when working with persistent heritage functions,
	 * as some child nodes will not be supposed to inherit some parameters.
	 *
	 * @param string $strAs
	 * @param string $strName
	 * @param bool $bolSkip If true, the element is only skipped during an persistent heritage operation.
	 * @return void
	 */
	public function noInherit($strAs, $strName='_ALL', $bolSkip=false) {
		$this->arrNoHeritage[$strAs][$strName] = $bolSkip;
	}

	/**
	 * This function accepts the inheritance brought through the inherit() function.
	 *
	 * @param mixed $mxtValue The inharitence value
	 * @param string $strAs Type of inheritence (attribute, link, dataset or value)
	 * @param string $strName The attribute/dataset/link name
	 * @param bool $bolPersistent If set, the inherit function will be called again, causing to pass the inheritance to all child nodes
	 * @return void
	 */
	public function passHeritage($strAs, $mxtValue, $strName, $bolPersistent) {
		$arrModes = array('attribute', 'value', 'link', 'dataset');
		if (in_array($strAs, $arrModes)) {
			if (!array_key_exists($strName, $this->arrNoHeritage[$strAs]) && !array_key_exists('_ALL', $this->arrNoHeritage[$strAs])) {
				if ($strAs == 'attribute')
					$this->setAttribute($strName, $mxtValue);
				if ($strAs == 'value')
					$this->setValue($mxtValue);
				if ($strAs == 'link')
					$this->setLink($strName, $mxtValue);
				if ($strAs == 'dataset')
					$this->setDataset($mxtValue, $strName);
			} else {
				if ($this->arrNoHeritage[$strAs][$strName] == false)
					$bolPersistent = false;
			}
			if ($bolPersistent)
				$this->inherit($strAs, $mxtValue, $strName, $bolPersistent);
		} else
			throw new Exception('Invalid operation mode: '.$strAs.'. Use "attribute", "value", "link" or "dataset" instead.');
	}
}

?>
