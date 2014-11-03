<?php
namespace Xily;

/**
 * Exception class for Xily-specific exceptions
 */
class Exception extends \Exception {}

/**
 * The Xml class is being used to work with XML structures.
 * It can be used to parse an XML file/string and to work dynamically
 * with this information or to manipulate the XML structure.
 *
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 2.0 (2013-11-13)
 * @package Xily
 * @copyright Copyright (c) 2009-2013, Peter-Christoph Haider
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class Xml extends Base {
	/** @var string The tag type (String). */
	public $strTag;
	/** @var string The number of the node within the current list (Integer). */
	public $intIndex;
	/** @var string The value of the node. */
	public $strValue;
	/** @var array The array containing the node's attributes. */
	public $arrAttributes = array();
	/** @var array The array containing the node's children. */
	public $arrChildren = array();
	/** @var array The array containing the node's CDATA nodes. */
	public $arrCdata = array();
	/** @var array The array containing the structure of CDATA and XML nodes. */
	public $arrContent = array();
	/** @var Xml Pointer to the root object. */
	public $objRoot;
	/** @var Xml Pointer to the parent node. */
	public $objParent;
	/** @var Xml Result container for the parse function. */
	public $xmlResult;
	/** @var Array List of tags that should be displayed as open, even if the have no value */
	public static $OPEN_TAGS = array('a', 'u', 'i', 'label', 'b', 'div', 'iframe', 'textarea', 'script', 'span', 'ul', 'li', 'section', 'select');

	/**
	 * Constructor
	 *
	 * @param string $strTag The tag name
	 * @param string|null $strValue The CDATA value
	 * @param array  $arrAttributes Associative array with attributes
	 * @param Xml|Bean $objParent The parent element
	 */
	public function __construct($strTag='xml', $strValue=null, $arrAttributes=array(), $objParent=null) {
		// Initialize the node's attributes
		$this->strTag = $strTag;
		$this->strValue = $strValue;
		$this->arrAttributes = is_array($arrAttributes) ? $arrAttributes : array();
		// DEBUG
		// $this->intIndex = $intIndex;
		if ($objParent) {
			$this->objRoot = $objParent->root();
			$this->objParent = null;
		} else {
			$this->intIndex = 0;
			$this->objRoot = $this;
			$this->objParent = $objParent;
		}

		// Initialize the node's building functions.
		// Child nodes may have additional functions to handle the tag's events such as
		// opening or closing the tag.
		$this->build();
	}

	/**
	 * Remove all children on destruction
	 */
	public function __destruct() {
		$this->removeChildren();
	}

	/**
	 * Converts an XML node to a string
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}

	/**
	 * The build() function initializes the node and its children
	 * Nothing to do here besides parsing the sub structure of the node.
	 * Deviations of Xml, e.g. Beans, may have different build functions.
	 */
	public function build() {}

	/**
	 * Loads the XML data from a string or file
	 *
	 * @param string $strXML Name of the XML file or XML string
	 * @param bool $bolLoadFile Load from file or parse locally
	 * @return Xml
	 */
	public static function create($strXML='', $bolLoadFile=false) {
		return $strXML == '' ? new Xml() : self::returnNode($strXML, new Xml(), $bolLoadFile);
	}

	/**
	 * Initialize the parser by loading a file or an XML string
	 *
	 * @param string $strXML Name of the XML file or XML string
	 * @param Xml|Bean $xmlMom Parent node
	 * @param  bool $bolLoadFile Load from file or parse locally
	 * @return Xml|Bean
	 */
	public static function returnNode($strXML, $xmlMom, $bolLoadFile) {
		try {
			if ($bolLoadFile)
				$strXML = file_get_contents($strXML);
		} catch (Exception $e) {
			throw new Exception("Could not read file: ".$strXML);
		}

		if ($xmlResult = $xmlMom->parse($strXML))
			return $xmlResult;

		throw new Exception('Could not create XML node due to an parsing error.');
	}

	/**
	 * Parses an XML string
	 *
	 * @param string $strXML
	 * @return Xml|bool
	 * @category ixml
	 */
	public function parse($strXML) {
		$this->arrOpenTags = array();
		$this->count = 0;
		$this->space = 0;

		$this->objParser = xml_parser_create();
		xml_parser_set_option($this->objParser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($this->objParser, XML_OPTION_SKIP_WHITE, 1);
		xml_set_object($this->objParser, $this);
		xml_set_element_handler($this->objParser, 'startTagCallback', 'endTagCallback');
		xml_set_character_data_handler($this->objParser, 'cdataCallback');

		if (xml_parse($this->objParser, $strXML, true) == 0)
			throw new Exception('Could not parse XML file - sorry! Error ('.xml_get_error_code($this->objParser).") on Line ".xml_get_current_line_number($this->objParser).': '.xml_error_string(xml_get_error_code($this->objParser)));

		xml_parser_free($this->objParser);
		return $this->xmlResult;
	}

	/**
	 * Creates a new XML node
	 *
	 * (Wrapper function to simplify Bean creation)
	 *
	 * @param string $strTag The tag name
	 * @param array $arrAttributes List of attributes
	 * @param Xml $xmlParent Parent node
	 * @return Xml
	 */
	public function createNode($strTag='', $arrAttributes=array(), $xmlParent=null) {
		return new Xml($strTag, null, $arrAttributes, $xmlParent);
	}

	/**
	 * Callback for start tags
	 *
	 * @param  resource $objParser Parser resource
	 * @param  string $strTag Tag name
	 * @param  array $arrAttributes List of attributes
	 * @return void
	 */
	private function startTagCallback($objParser, $strTag, $arrAttributes) {
		$xmlCurrent = $this->arrOpenTags ? end($this->arrOpenTags) : null;

		$xmlChild = $this->createNode($strTag, $arrAttributes, $xmlCurrent);
		$this->arrOpenTags[] = $xmlChild;

		if (isset($xmlCurrent))
			$xmlCurrent->addChild($xmlChild);
		else
			$this->xmlResult = $xmlChild;
	}

	/**
	 * Callback for end tags
	 *
	 * @param  resource $objParser Parser resource
	 * @param  string $strTag Tag name
	 * @return void
	 */
	private function endTagCallback($objParser, $strTag) {
		$xmlCurrent = array_pop($this->arrOpenTags);
		$xmlCurrent->reindex();
	}

	/**
	 * Callback for CDATA
	 *
	 * @param  resource $objParser Parser resource
	 * @param  string $strCdata CDATA string
	 * @return void
	 */
	private function cdataCallback($objParser, $strCdata) {
		if (trim($strCdata) != '') {
			$xmlCurrent = $this->arrOpenTags[sizeof($this->arrOpenTags)-1];
			$xmlCurrent->addCdata(
				(in_array(substr($strCdata, 0, 1), array("\n", "\r", "\t", " ", "\x0B", "\0")) ? ' ' : '')
				.$strCdata
				.(in_array(substr($strCdata, -1), array("\n", "\r", "\t", " ", "\x0B", "\0")) ? ' ' : '')
			);
		}
	}

	// =========== Result functions ===========

	/**
	 * Returns the first node found with the specified ID
	 *
	 * @param string $strNodeId [id]
	 * @return Xml
	 * @category iXML
	 */
	public function getNodeById($strNodeId) {
		$objNode = false;
		if (isset($this->arrAttributes['id']) && $this->arrAttributes['id'] == $strNodeId) {
			return $this;
		} else {
			if ($this->arrChildren) {
				for ($i = 0 ; $i < sizeof($this->arrChildren) ; $i++) {
					if ($objNode = $this->arrChildren[$i]->getNodeById($strNodeId))
						return $objNode;
				}
				if (!$objNode)
					return false;
			} else {
				// Node has no children...
				return false;
			}
		}
	}

	/**
	 * Returns an array of nodes or attributes
	 *
	 * @param string|array $mxtPath [path]
	 * @return array
	 * @category iXML
	 */
	public function getNodesByPath($mxtPath) {
		$arrResult = array();
		if (!is_array($mxtPath)) {
			// Convert the path string to an array ({Complete chain with filter}, {tag}, {filter})
			$strSpecial = substr($mxtPath, 0, 1) == '.' ? '.' : '';
			if (!preg_match_all('/\s*([^.(\s]+)\s*(?:\((.*?)\))?/', $mxtPath, $mxtPath, PREG_SET_ORDER))
				return array();

			$mxtPath[0][0] = $strSpecial.$mxtPath[0][0];
			$mxtPath[0][1] = $strSpecial.$mxtPath[0][1];

			// Check if the path starts with the correct root tag
			// If the path onsists only of the root tag, return a dublicate of the root
			if (sizeof($mxtPath) == 2 && $mxtPath[0] == '')
				if ($mxtPath[1] == 'this')
					return array($this);
				elseif ($mxtPath[1] == 'this')
					return array($this->parent());
				elseif ($mxtPath[1] == 'root')
					return array($this->root());
				else
					return array();
		}

		$arrCurrent = array_shift($mxtPath);
		$arrFilter = $this->buildFilter($arrCurrent, 1);

		// TESTING:  $this->probe('getNodesByPath', 'Filter: '.json_encode($arrFilter));

		// Get all children with mathing filter settings
		$xlsChildren = $this->children($arrFilter['tag'], $arrFilter['index'], $arrFilter['value'], $arrFilter['attributes'], $arrFilter['path']);

		if ($xlsChildren) {
			// TESTING:  $this->probe('getNodesByPath', sizeof($xlsChildren).' child(ren) found', 3);
			if ($mxtPath) {
				// TESTING:  $this->probe('getNodesByPath', 'There are sill '.sizeof($mxtPath).' elements in the path.', 4);
				$xlsResult = array();
				foreach ($xlsChildren as $xmlChild) {
					$xlsGrandchildren = $xmlChild->getNodesByPath($mxtPath);
					if ($xlsGrandchildren)
						$xlsResult = array_merge($xlsResult, $xlsGrandchildren);
				}
				return $xlsResult;
			// TESTING: } else {
			// TESTING:  $this->probe('getNodesByPath', 'Path finished. Returning the children', 5);
			}
		}
		return $xlsChildren;
	}

	/**
	 * Will return the fist matching node to be found.
	 *
	 * @param string $strNodePath [path]
	 * @return Xml|false
	 * @category iXML
	 */
	public function getNodeByPath($strNodePath) {
		if ($arrResult = $this->getNodesByPath($strNodePath))
			return $arrResult[0];
		return false;
	}

	/**
	 * This is an assisting method for getNodesByPath, in order to shape a path link to be used by the children() method
	 * Anatomy (example): node(@attribute == 'condition', id == 'myid')
	 * Returns an array containing the filter attribute as key and filter + value as value,
	 * e.g. $arrFilter = array('tag' => 'node', 'id' => 'myid', 'attributes' => array('attribute' => 'condition'));
	 *
	 * @param array|string $mxtNode The current node including filter (array format: [{tag including filter}, {tag}, {filter}]
	 * @return array|bool
	 */
	public function buildFilter($mxtNode) {
		if (!is_array($mxtNode)) {
			if (!preg_match_all('/\s*([^.(\s]+)\s*(?:\((.*?)\))?/', $mxtNode, $mxtNode, PREG_SET_ORDER))
				return false;
			$mxtNode = array_shift($mxtNode);
		}

		$arrNode = array(
			'tag' => $mxtNode[1],
			'attributes' => array(),
			'path' => array(),
			'index' => null,
			'value' => null
		);

		// Check, if the node has a filter
		$arrFixed = array('#index', '#value');
		if (isset($mxtNode[2])) {
			$arrFilters = $this->equationBuild($mxtNode[2], 1);

			foreach ($arrFilters as $arrFilter) {
				if (substr($arrFilter['attribute'], 0, 1) == '#' && in_array($arrFilter['attribute'], $arrFixed)) {
					$arrFilter['attribute'] = substr($arrFilter['attribute'], 1);
					$arrNode[$arrFilter['attribute']] = array($arrFilter['value'], $arrFilter['operator']);
				} else {
					if (substr($arrFilter['attribute'], 0, 1) == '@') {
						// If the filter starts with @, just filter a local attribute
						$arrFilter['attribute'] = substr($arrFilter['attribute'], 1);
						$arrNode['attributes'][] = array($arrFilter['attribute'], $arrFilter['value'], $arrFilter['operator']);
					} else {
						// Otherwise, check for a complete path
						$arrNode['path'][] = array($arrFilter['attribute'], $arrFilter['value'], $arrFilter['operator']);
					}
				}
			}
		}

		return $arrNode;
	}

	/**
	 * Trace a single node attribute or property.
	 * Works like getNodeByPath, but it will not return a whole object, but will
	 * only evaluate the node's property, e.g. node.subnode.@value
	 *
	 * @param string $strPath [path]
	 * @return string
	 * @category iXML/Wrap
	 */
	public function trace($strPath) {
		// Convert the path to an array
		$arrPath = explode(".", $strPath);

		if (sizeof($arrPath) == 1 && substr($strPath, 0, 1) == '@') {
			// The path refers to a local attribute
			$strPath = substr($strPath, 1);
			if ($strPath == 'id')
				return $this->id();
			elseif ($strPath == 'index')
				return $this->index();
			elseif ($strPath == 'value')
				return $this->dump();
			else
				return $this->attribute($strPath);
		} else {
			// Check, if the last chain link is an attribute
			// Attributes start with a @-symbol
			if (substr($arrPath[sizeof($arrPath)-1], 0, 1) == "@") {
				$strAttribute = substr(array_pop($arrPath), 1);
				$strPath = implode(".", $arrPath);
			}

			$xmlNode = $this->getNodeByPath($strPath);

			if ($xmlNode instanceof Xml) {
				if (isset($strAttribute)) {
					if ($strAttribute == "id")
						return $xmlNode->id();
					elseif ($strAttribute == "index")
						return $xmlNode->index();
					elseif ($strAttribute == 'value')
						return $xmlNode->dump();
					else
						return $xmlNode->attribute($strAttribute) ;
				} else
					return $xmlNode->dump();
			}
			return false;
		}
	}

	/**
	 * Returns the root object
	 *
	 * @return Xml
	 * @category iXML
	 */
	public function root() {
		return $this->objRoot;
	}

	/**
	 * Returns the parent of the current node
	 * If the node has no parent / is root, the function returns false
	 *
	 * @return Xml
	 * @category iXML
	 */
	public function parent() {
		return $this->objParent;
	}

	/**
	 * Return the specified child of the node.
	 * If no index specified, it will return the first node
	 *
	 * @param int|string $mxtID The child index (int) or tag type (string) [index]
	 * @return Xml
	 * @category iXML
	 */
	public function child($mxtID=0) {
		if (is_numeric($mxtID)) {
			$mxtID = (int) $mxtID;
			if (sizeof($this->arrChildren) > 0 && $mxtID <= sizeof($this->arrChildren))
				return $this->arrChildren[$mxtID];
		} else {
			$xlsChildren = $this->children($mxtID);
			if (sizeof($xlsChildren) > 0)
				return $xlsChildren[0];
		}
		return false;
	}

	/**
	 * Checks a single attribute (Utility function for Xml::chilren)
	 *
	 * @see Xml::chilren
	 * @param string $strValue the value to check
	 * @param array|string $mxtFilter Either the value to compare to or an array with an additional operator [value, operator]
	 */
	private function checkProperty($strValue, $mxtFilter) {
		if (is_array($mxtFilter))
			return $this->equationCheck($strValue, $mxtFilter[0], $mxtFilter[1]);
		else
			return $this->equationCheck($strValue, $mxtFilter);
	}

	/**
	 * Returns the node's children
	 *
	 * @param string|array $mxtTag The node's tag (single value or filter array [value, operator]) [n:tag]
	 * @param int|array $mxtIndex The node's index (single value or filter array [value, operator]) [n:index]
	 * @param string|array $mxtValue The node's value (single value or filter array [value, operator]) [n:value]
	 * @param array $arrAttributes The node's attribute (filter array [[attribute, value, operator], ...]) [a:attributes]
	 * @param array $arrPath Checks, if a certain sub-path applies to the node (filter array [[path, value, operator], ...]) [a:path]
	 * @return array Array of XML nodes
	 * @category iXML
	 */
	public function children($mxtTag=null, $mxtIndex=null, $mxtValue=null, $arrAttributes=array(), $arrPath=array()) {
		// TESTING:  $this->probe('children', 'Getting children: $mxtTag="'.$mxtTag.'", $mxtIndex="'.$mxtIndex.'", $mxtValue="'.$mxtValue.'"', 1, $arrAttributes);

		$arrChildren = array();
		foreach ($this->arrChildren as $xmlChild) {
			if (
				(is_null($mxtTag) || $this->checkProperty($xmlChild->tag(), $mxtTag))
				&& (is_null($mxtIndex) || $this->checkProperty($xmlChild->index(), $mxtIndex))
				&& (is_null($mxtValue) || $this->checkProperty($xmlChild->value(), $mxtValue))
			) {
				// Check the attributes
				$bolPass = true;

				if ( $arrAttributes === null )
					$arrAttributes = array();

				foreach ($arrAttributes as $attribute => $arrFilter) {
					if (is_numeric($attribute))
						$attribute = array_shift($arrFilter);
					if (!$bolPass = $this->checkProperty($xmlChild->attribute($attribute), $arrFilter))
						break;
				}
				foreach ($arrPath as $arrFilter) {
					// Check, if the query is referring to an attribute or a node value
					$strPath = $arrFilter[0];
					$strAttribute = false;
					$arrPath = explode(".", $strPath);
					if (substr($arrPath[sizeof($arrPath)-1], 0, 1) == "@") {
						$strAttribute = substr(array_pop($arrPath), 1);
						$strPath = implode(".", $arrPath);
					}

					$subPass = false;
					foreach ($xmlChild->getNodesByPath($strPath) as $xmlNode) {
						if ($this->equationCheck($strAttribute ? $xmlNode->attribute($strAttribute) : $xmlNode->value(), $arrFilter[1], $arrFilter[2]))
							$subPass = true;
							break;
					}
					if (!$subPass)
						$bolPass = false;
					// if (!$bolPass = $this->equationCheck($xmlChild->trace($arrFilter[0]), $arrFilter[1], $arrFilter[2]))
				}

				if ($bolPass)
					$arrChildren[] = $xmlChild;
			}
		}

		return $arrChildren;
	}

	/**
	 * Returns the CDATA entries of the tag
	 *
	 * @param integer $intIndex The index of the CDATA element (Optional) [n:index]
	 * @return array|string
	 * @category iXML/Wrap
	 */
	public function cdata($intIndex=null) {
		if (!is_null($intIndex))
			return $this->arrCdata[$intIndex];
		else
			return $this->arrCdata;
	}

	/**
	 * Returns the Content (Children and CDATA) of the Tag
	 *
	 * @param integer $intIndex The index of the content element (Optional) [n:index]
	 * @return array|string|Xml
	 * @category iXML/Wrap
	 */
	public function content($intIndex=null) {
		if ($this->hasContent()) {
			if (!is_null($intIndex)) {
				if ($intIndex == 0 && $this->hasValue()) {
					// The first content element is the nodes value
					return $this->strValue;
				} else {
					if ($this->hasValue())
						$intIndex--;
					if ($intIndex && $this->arrContent[$intIndex]) {
						if ($this->arrContent[$intIndex][0] == 0) {
							// Content is CDATA
							return $this->arrCdata[$this->arrContent[$intIndex][1]];
						} else {
							// Content is XML
							return $this->arrChildren[$this->arrContent[$intIndex][1]];
						}
					}
				}
			}
			// If no index is specified, return the complete content
			// All CDATA values will be stripped from tabs and multiple spaces
			$arrResult = array();
			if ($this->hasValue())
				$arrResult[] = $this->strStripInline($this->value());
			foreach ($this->arrContent as $link) {
				if ($link[0] == 0)
					$arrResult[] = $this->strStripInline($this->arrCdata[$link[1]]);
				else
					$arrResult[] = $this->arrChildren[$link[1]];
			}
			return $arrResult;
		} else
			return array();
	}

	/**
	 * Returns a dump of the node's content.
	 *
	 * @return string
	 * @category iXML/Wrap
	 */
	public function dump() {
		$strContent = $this->strValue;
		foreach ($this->arrContent as $link) {
			if ($link[0] == 0)
				$strContent .= $this->strStripInline($this->arrCdata[$link[1]], 0);
			else
				$strContent .= $this->arrChildren[$link[1]]->dump();
		}
		return $strContent;
	}

	/**
	 * Identifies the zero-indexed position of this XML object within the context of its parent.
	 *
	 * @return int
	 * @category iXML/Wrap
	 */
	public function index() {
		return $this->intIndex;
	}

	/**
	 * Returns the node's ID
	 *
	 * @return string
	 * @category iXML/Wrap
	 */
	public function id() {
		return $this->attribute('id');
	}

	/**
	 * Returns the node's tag
	 *
	 * @return string
	 * @category iXML/Wrap
	 */
	public function tag() {
		return $this->strTag;
	}

	/**
	 * Returns the node's ID, if any
	 *
	 * @return string
	 * @category iXML/Wrap
	 */
	public function value() {
		return $this->strValue;
	}

	/**
	 * Returns the value of an attribute
	 *
	 * @param string $strKey [key]
	 * @return string Attibute value
	 * @category iXML/Wrap
	 */
	public function attribute($strKey) {
		if (!(is_string($strKey) || is_int($strKey)))
			throw new Exception('Invalid attribute key: '.$strKey);

		return isset($this->arrAttributes[$strKey]) ? $this->arrAttributes[$strKey] : null;
	}

	/**
	 * Returns an associative array containing the node's attributes
	 *
	 * @return array
	 * @category iXML
	 */
	public function attributes() {
		return $this->arrAttributes;
	}

	/**
	 * Returns a copy of the given XML object.
	 *
	 * @return object
	 * @category iXML
	 */
	public function copy() {
		return clone $this;
	}

	// ============== Setting functions ==============

	/**
	 * Sets the index of the node
	 *
	 * @param string $intValue [value]
	 * @category iXML
	 * @return Xml
	 */
	public function setIndex($intValue) {
		$this->intIndex = $intValue;
		return $this;
	}

	/**
	 * Sets an attribute
	 *
	 * @param string $strAttribute [key]
	 * @param string $strValue [value]
	 * @category iXML
	 * @return Xml
	 */
	public function setAttribute($strAttribute, $strValue) {
		$this->arrAttributes[$strAttribute] = $strValue;
		return $this;
	}

	/**
	 * Sets the value of the node
	 *
	 * @param string $strValue [n:value]
	 * @category iXML
	 * @return Xml
	 */
	public function setValue($strValue=null) {
		$this->strValue = $strValue;
		return $this;
	}

	/**
	 * Changes the tag of the node
	 *
	 * @param string $strTag [value]
	 * @category iXML
	 * @return Xml
	 */
	public function setTag($strTag) {
		$this->strTag = $strTag;
		return $this;
	}

	/**
	 * Sets the root object for a node or its children
	 *
	 * @param object $xmlRoot The new root object
	 * @param bool $bolPersistent If $bolPersistent=true, the object passes the new parent object to all ancestors
	 * @return Xml
	 */
	public function setRoot($xmlRoot, $bolPersistent=true) {
		$this->objRoot = $xmlRoot;
		if ($bolPersistent)
			foreach ($this->arrChildren as $child)
				$child->setRoot($xmlRoot, 0, $bolPersistent);

		return $this;
	}

	/**
	 * Sets the parent object for a node or its children
	 *
	 * @param object $xmlParent The new parent object
	 * @return Xml
	 */
	public function setParent($xmlParent) {
		$this->objParent = $xmlParent;
		return $this;
	}

	/**
	 * Changes the content of a CDATA element
	 *
	 * @param integer $intIndex Index of the CDATA element [index]
	 * @param string $strValue New Value of the CDATA element [value]
	 * @category iXML
	 * @return Xml
	 */
	public function setCdata($intIndex, $strValue) {
		if ($intIndex < sizeof($this->arrCdata))
			$this->arrCdata[$intIndex] = $strValue;

		return $this;
	}

	/**
	 * Adds a new CDATA element
	 *
	 * @param string $strValue New Value of the new CDATA element
	 * @param int $intIndex The desired position of the new CDATA element (overrides $intContentIndex!)
	 * @param int $intContentIndex The desired position within the content stream
	 * @see Xml::addChild
	 * @return Xml
	 */
	public function addCdata($strValue, $intIndex=null, $intContentIndex=null) {
		if (isset($intIndex) && $intIndex < sizeof($this->arrCdata)) {
			for ($i = sizeof($this->arrCdata) ; $i > $intIndex ; $i--)
				$this->arrCdata[$i] = $this->arrCdata[$i-1];

			$this->arrCdata[$intIndex] = $strValue;
			$intContentIndex = is_null($intContentIndex)?$this->getContentIndex($intIndex, 0):$intContentIndex;
		} else {
			// If there is no content so far, the first CDATA entry is the node's value
			if (sizeof($this->arrContent) > 0) {
				$this->arrCdata[] = $strValue;
				// DEBUG: Nicht eher "sizeof($this->arrContent) ???
				$intIndex = is_null($intContentIndex) ? sizeof($this->arrChildren) : $this->getContentTarget($intContentIndex);
			} else {
				$this->strValue .= $strValue;
				return true;
			}
		}
		if (isset($intContentIndex)) {
			if ($intContentIndex > 0) {
				// Check, if the desired content index is valid; The content index is valid,
				// if it's smaller than the currently assigned content index.
				if ($intCheckIndex = $this->getContentIndex($intIndex)) {
					if ($intCheckIndex < $intContentIndex-1)
						throw new Exception('Content index is not valid. Select an index > '.$intCheckIndex);
				}
			} else {
				// If the ContentIndex is negative, the content index is forced without prior checking
				$intContentIndex = $intContentIndex * (-1);
			}
		}
		$this->addContentEntry($intIndex, 0, $intContentIndex);

		return $this;
	}

	/**
	 * Adds a new child to the node
	 *
	 * This adds a new node at the end of the XML and content array:
	 * <code>
	 * $xml->addChild(new Xml('div', 'hello world'));
	 * </code>
	 *
	 * This adds a new node at a specific position within the XML structure (index 3)
	 * igonring its position within the content stream:
	 * <code>
	 * $xml->addChild(new Xml('div', 'hello world'), 3);
	 * </code>
	 *
	 * This adds a new node at a specific position within the content stream,
	 * automatically positioning it within the XML array.
	 * <code>
	 * $xml->addChild(new Xml('div', 'hello world'), null, 2);
	 * </code>
	 *
	 * @param object $xmlNode [node]
	 * @param int $intIndex The desired position of the new child node (overrides $intContentIndex!) [n:index]
	 * @param int $intContentIndex The desired position within the content stream [n:content_index]
	 * @return Xml
	 * @category iXML
	 */
	public function addChild($xmlNode, $intIndex=null, $intContentIndex=null) {
		if (!($xmlNode instanceof Xml))
			throw new Exception('Could not add XML node. Function requires a Xml object.');

		$xmlNode->detach();
		$xmlNode->setParent($this);
		$xmlNode->setRoot($this->root());
		if (!is_null($intIndex) && $intIndex < sizeof($this->arrChildren)) {
			for ($i = sizeof($this->arrChildren) ; $i > $intIndex ; $i--) {
				$this->arrChildren[$i] = $this->arrChildren[$i-1];
				$this->arrChildren[$i]->setIndex($i);
			}
			$this->arrChildren[$intIndex] = $xmlNode;
			$this->arrChildren[$intIndex]->setIndex($intIndex);
			$intContentIndex = is_null($intContentIndex) ? $this->getContentIndex($intIndex) : $intContentIndex;
		} else  {
			// Check if the XML tag should be inserted at a specific position of the content stream
			$intIndex = $intContentIndex ? $this->getContentTarget($intContentIndex) : sizeof($this->arrChildren);
			$this->arrChildren[] = $xmlNode;
		}
		if ($intContentIndex) {
			if ($intContentIndex > 0) {
				// Check, if the desired content index is valid
				if ($intCheckIndex = $this->getContentIndex($intIndex))
					if ($intCheckIndex < $intContentIndex-1)
						throw new Exception('Content index is not valid. Select an index > '.$intCheckIndex);
			} else {
				// If the ContentIndex is negative, the content index is forces without prior checking
				$intContentIndex = $intContentIndex * (-1);
			}
		}
		$this->addContentEntry($intIndex, 1, $intContentIndex);

		return $this;
	}

	/**
	 * Adds serveral children
	 *
	 * @param array $arrChildren Array containing the XML elements [children]
	 * @param int $intIndex Target index of the child nodes [n:index]
	 * @return Xml
	 * @category iXML
	 */
	public function addChildren($arrChildren, $intIndex=null) {
		if (!is_array($arrChildren))
			throw new Exception('Invalid data type. Array expected.');

		foreach ($arrChildren as $child) {
			if ($child instanceof Xml) {
				$this->addChild($child, $intIndex);
				if (is_numeric($intIndex))
					$intIndex++;
			} else
				throw new Exception('Found invalid datatype among array values. Xml is expected.');
		}
		return $this;
	}

	/**
	 * Adds a content element (either CDATA or an XML node)
	 *
	 * @param Xml|string $mxtNode The content node
	 * @param int $intIndex The sequence index
	 * @return bool
	 */
	public function addContent($mxtNode, $intIndex=null) {
		if (is_null($intIndex)) {
			$intIndex = sizeof($this->arrContent)-1;
			$bolAdd = true;
		}
		if (is_string($mxtNode)) {
			if ($intIndex == 0) {
				// The first content element equals the value the node's value
				$this->strValue = $mxtNode.$this->strValue;
			} elseif ($intIndex == 1 && $this->hasContent()) {
				// If the node has a value, add the new content to it
				$this->strValue .= $mxtNode;
			} else {
				if ($this->hasValue())
					$intIndex--;
				if ($this->arrContent[$intIndex][0] == 0 && $intIndex < sizeof($this->arrContent)-1) {
					// If the defined index is alread CDATA, then merge it with the new one
					$this->arrCdata[$this->arrContent[$intIndex][1]] = $mxtNode.$this->arrCdata[$this->arrContent[$intIndex][1]];
				} elseif ($this->arrContent[$intIndex-1][0] == 0 && $intIndex > 0) {
					// Same goes for the prior one
					$this->arrCdata[$this->arrContent[$intIndex-1][1]] .= $mxtNode;
				} elseif ($bolAdd) {
					// Add a new CDATA node
					$this->addCdata($mxtNode);
				} else {
					// Insert a new CDATA node
					$intTarget = $this->getContentTarget($intIndex, 0);
					$this->addCdata($mxtNode, $intTarget, $intIndex);
				}
			}
			return true;
		} elseif ($mxtNode instanceof Xml) {
			if ($bolAdd) {
				// Add a new XML node
				$this->addChild($mxtNode);
			} else {
				// Insert a new XML node
				$intTarget = $this->getContentTarget($intIndex);
				$this->addChild($mxtNode, $intTarget, $intIndex*(-1));
			}
			return true;
		} else
			return false;
	}

	/**
	 * Returns a valid XML/CDATA index for a desired content index
	 *
	 * @param int $intIndex
	 * @param int $intType
	 * @return int
	 */
	private function getContentTarget($intIndex, $intType=1) {
		for ($i = $intIndex ; $i < sizeof($this->arrContent) ; $i++) {
			if ($this->arrContent[$i][0] == $intType)
				return $this->arrContent[$i][1];
		}
		if ($intType == 0)
			return sizeof($this->arrCdata);
		else
			return sizeof($this->arrChildren);
	}

	/**
	 * Returns the content index of a specified XML/CDATA index
	 *
	 * @param int $intTarget
	 * @param int $intType
	 * @return int
	 */
	private function getContentIndex($intTarget, $intType=1) {
		for ($i = 0 ; $i < sizeof($this->arrContent) ; $i++) {
			if ($this->arrContent[$i][0] == $intType && $this->arrContent[$i][1] == $intTarget)
				return $i;
		}
		return false;
	}

	/**
	 * Inserts a new element in the content stream
	 *
	 * @param int $intIndex Index of the content element
	 * @param int $intTarget Target index within the content stream
	 * @param int $intType Type of the content element
	 * @return void
	 */
	private function addContentEntry($intIndex, $intType=1, $intTarget=null) {
		if (isset($intTarget)) {
			// Move all following content items one index up to make space for the new content item
			for ($i = sizeof($this->arrContent) ; $i > $intTarget ; $i--) {
				if ($this->arrContent[$i-1][0] == $intType)
					$this->arrContent[$i] = array($this->arrContent[$i-1][0], $this->arrContent[$i-1][1]+1);
				else
					$this->arrContent[$i] = array($this->arrContent[$i-1][0], $this->arrContent[$i-1][1]);
			}
			$this->arrContent[$intTarget] = array($intType, $intIndex);
		} else {
			// Add a new content element at the end of the content list
			if ($intType == 0)
				$this->arrContent[] = array(0, sizeof($this->arrCdata)-1);
			else
				$this->arrContent[] = array(1, sizeof($this->arrChildren)-1);
		}
	}

	// ============== Checking functions ==============

	/**
	 * Checks, if the node has a value
	 *
	 * @return bool
	 * @category iXML/Wrap
	 */
	public function hasValue() {
		return !is_null($this->strValue);
	}

	/**
	 * Checks, if the node has children
	 *
	 * @return bool
	 * @category iXML/Wrap
	 */
	public function hasChildren() {
		return (bool) $this->arrChildren;
	}

	/**
	 * Counts the node's children
	 *
	 * @return int
	 * @category iXML/Wrap
	 */
	public function countChildren() {
		return sizeof($this->arrChildren);
	}

	/**
	 * Checks, if the node has any CDATA tags
	 *
	 * @return bool
	 * @category iXML/Wrap
	 */
	public function hasCdata() {
		return (bool) $this->arrCdata;
	}

	/**
	 * Counts the node's CDATA tags
	 *
	 * @return int
	 * @category iXML/Wrap
	 */
	public function countCdata() {
		return sizeof($this->arrCdata);
	}

	/**
	 * Checks, if the node has any content
	 *
	 * @return bool
	 * @category iXML/Wrap
	 */
	public function hasContent() {
		if ($this->hasValue())
			return true;
		if ($this->hasChildren())
			return true;
		if ($this->hasCdata())
			return true;
	}

	/**
	 * Checks, if the node has attributes
	 *
	 * @return bool
	 * @category iXML/Wrap
	 */
	public function hasAttributes() {
		return (bool) $this->arrAttributes;
	}

	/**
	 * Counts the node's attributes
	 *
	 * @return int
	 * @category iXML/Wrap
	 */
	public function countAttributes() {
		return sizeof($this->arrAttributes);
	}

	/**
	 * Checks, if the node has a certain attriubte
	 * You can check for one single attribute or you can apply a whole attribute list (represented by an array).
	 * Checking modes are
	 * - "normal": Checks, if the node has the specified attributes
	 * - "strict": Checks, if the node has exactly (only) the specified attributes
	 * - "count" : Counts the number of deviations;
	 *
	 * @param string|array $mxtAttributes [key]
	 * @param string $mode [n:mode]
	 * @return bool
	 * @category iXML/Wrap
	 */
	public function hasAttribute($mxtAttributes, $mode="normal") {
		if ($this->hasAttributes()) {
			if (is_array($mxtAttributes)) {
				$numDeviations = 0;
				if (sizeof($this->arrAttributes) > sizeof($mxtAttributes))
					if ($mode == "strict") return false;

				foreach ($mxtAttributes as $key => $value) {
					if (array_key_exists($key, $this->arrAttributes)) {
						if($this->arrAttributes[$key] != $value) {
							if ($mode == "strict") return false;
							$numDeviations++;
						}
					} else {
						if ($mode != "count") return false;
						$numDeviations++;
					}
				}
				if ($mode == "count")
					return $numDeviations;
				else
					return true;
			} else {
				if (array_key_exists($mxtAttributes, $this->arrAttributes))
					return true;
				else
					return false;
			}
		} else
			return false;
	}

	/**
	 * Checks whether the node has a parent or not
	 *
	 * @return bool
	 * @category iXML/Wrap
	 */
	public function hasParent() {
		if ($this->objParent)
			return true;
		else
			return false;
	}

	// ============= Assisting functions =============

	/**
	 * Checks, if a a certain attribute is true
	 *
	 * @param string $strAttribute [key]
	 * @return bool
	 * @category iXML/Wrap
	 */
	public function isTrue($strAttribute) {
		if ($strValue = $this->attribute($strAttribute)) {
			if (trim(strtolower($strValue)) == 'true')
				return true;
			else
				return false;
		} else
			return false;
	}

	/**
	 * Checks, if a a certain attribute is false
	 *
	 * @param string $strAttribute [key]
	 * @return bool
	 * @category iXML/Wrap
	 */
	public function isFalse($strAttribute) {
	// Checks, if a attribute is true or false
		if ($strValue = $this->attribute($strAttribute)) {
			if (trim(strtolower($strValue)) == 'false')
				return true;
			else
				return false;
		} else
			return false;
	}

	/**
	 * Identifies if a node corresponds to certain properties
	 *
	 * @param string $strTag
	 * @param int $intIndex
	 * @param string $strValue
	 * @param array $arrFilter
	 * @return bool
	 */
	public function isNode($strTag="", $intIndex="", $strValue="", $arrFilter="") {
		$numScore = 0;
		if ($strTag) {
			if ($this->strTag == $strTag) $numScore++;
		} else {
			$numScore++;
		}
		if ($intIndex) {
			if ($this->intIndex == $intIndex) $numScore++;
		} else {
			$numScore++;
		}
		if ($strValue) {
			if ($this->strValue == $strValue) $numScore++;
		} else {
			$numScore++;
		}
		if ($arrFilter) {
			if ($this->hasAttribute($arrFilter)) $numScore++;
		} else {
			$numScore++;
		}
		// If all requirements are matched, the score should be 6
		if ($numScore == 4)
			return true;
		else
			return false;
	}

	// =============  Shaping functions  =============

	/**
	 * Terminates the node
	 *
	 * @param bool $bolParent
	 * @category iXML/Wrap
	 */
	public function terminate() {
		// DEBUG: Reference couter on root und parent UNSET.
		if ($this->hasParent()) {
			$this->terminateChildren();
			$xmlParent = $this->objParent;
			$xmlParent->terminateChildren($this);
			$xmlParent->reindex();
		} else {
			// Suicide is not possible amoung nodes... ;-)
			throw new Exception('The node has no parent who could remove the node.');
		}
	}

	/**
	 * Supporting function for removeChild and removeChildren Kills either all or a specified child node
	 *
	 * @param object $xmlNode
	 * @return bool
	 */
	private function terminateChildren($xmlNode=null) {
		$bolKillAll = is_null($xmlNode);
		$bolRemoved = false;
		$i = 0;
		while ($i < sizeof($this->arrChildren)) {
			if ($bolKillAll || $xmlNode == $this->arrChildren[$i]) {
				$this->arrChildren[$i]->terminateChildren();
				array_splice($this->arrChildren, $i, 1);
				$this->removeContentEntry($i);
				$bolRemoved = true;
			} else
				$i++;
		}
		$this->arrChildren = array_values($this->arrChildren);
		return $bolRemoved;
	}

	/**
	 * Removes a specified child node
	 * The child node can either be defined by a path, an index or an XML object
	 *
	 * @param Xml|string|int $mxtChild [child]
	 * @return bool
	 * @category iXML
	 */
	public function removeChild($mxtChild) {
		if ($mxtChild instanceof Xml)
			return $mxtChild->terminate();
		elseif (is_string($mxtChild)) {
			if ($xmlChild = $this->getNodeByPath($mxtChild))
				return $xmlChild->terminate();
			else
				return false;
		} elseif (is_int($mxtChild)) {
			if (sizeof($this->arrChildren > $mxtChild)) {
				$this->arrChildren[$mxtChild]->terminate();
			} else
				return false;
		} else {
			// Variable type not supported
			return false;
		}
	}

	/**
	 * Removes a CDATA element
	 *
	 * @param int $intIndex Index of the CDATA element. If NULL all CDATA element will be removed. [n:index]
	 * @return bool
	 * @category iXML
	 */
	public function removeCdata($intIndex=null) {
		if (is_null($intIndex)) {
			$this->arrCdata = array();
			$arrContent = array();
			for ($i = 0 ; $i < sizeof($this->arrContent) ; $i++)
				if ($this->arrContent[$i][0] == 1)
					array_push($arrContent, array(1, $this->arrContent[$i][1]));
			$this->arrContent = $arrContent;
			return true;
		} else {
			if ($intIndex < sizeof($this->arrCdata)) {
				array_splice($this->arrCdata, $intIndex, 1);
				return $this->removeContentEntry($intIndex, 0);
			} else
				return false;
		}
	}

	/**
	 * Removes multiple children from a node
	 * If no identifier is specified, the function will remove all child nodes
	 * Additionally a XML path or array of XML objects can be used as identifier
	 *
	 * @param array|string $mxtChildren [n:children]
	 * @return bool
	 * @category iXML
	 */
	public function removeChildren($mxtChildren=null) {
		if (is_null($mxtChildren)) {
			return $this->terminateChildren();
		} elseif (is_array($mxtChildren) || is_string($mxtChildren)) {
			if (is_string($mxtChildren))
				$mxtChildren = $this->getNodesByPath($mxtChildren);
			if ($mxtChildren) {
				$bolSuccess = true;
				foreach ($mxtChildren as $xmlChild)
					if (!$xmlChild->terminate())
						$bolSuccess = false;
				return $bolSuccess;
			} else
				return false;
		} else
			throw new Exception('Arguement must be either an XML node or an array.');
	}

	/**
	 * Removes a content element (value, CDATA or XML)
	 *
	 * @param int $intIndex Content index. If NULL, all content elements will be removed [index]
	 * @return bool
	 * @category iXML
	 */
	public function removeContent($intIndex=null) {
		if (is_null($intIndex)) {
			$this->strValue = '';
			$this->arrContent = array();
			$this->arrCdata = array();
			$this->terminateChildren();
			return true;
		} else {
			if ($intIndex == 0 && $this->hasValue()) {
				$this->strValue = '';
				return true;
			} else {
				if ($this->hasValue())
					$intIndex--;
				if (sizeof($this->arrContent) > $intIndex) {
					if ($this->arrContent[$intIndex][0] == 1)
						return $this->removeChild($this->arrContent[$intIndex][1]);
					else
						return $this->removeCdata($this->arrContent[$intIndex][1]);
				} else
					return false;
			}
		}
	}

	/**
	 * Assisting function to update the content array after removing CDATA or XML
	 *
	 * @param int $intIndex Index of the XML or CDATA tag
	 * @param int $intType Content type (XML = 1; CDATA = 0)
	 * @return bool
	 */
	private function removeContentEntry($intIndex, $intType=1) {
		$intTarget = 0;
		for ($i = 0 ; $i < sizeof($this->arrContent) ; $i++) {
			if ($this->arrContent[$i][0] == $intType && $this->arrContent[$i][1] == $intIndex) {
				$this->arrContent = array_merge(array_slice($this->arrContent, 0, $i), array_slice($this->arrContent, $i+1));
				$intTarget = $i;
				// Update the reest of following content entries
				for ($z = $i ; $z < sizeof($this->arrContent) ; $z++) {
					if ($this->arrContent[$z][0] == $intType) {
						$this->arrContent[$z][1]--;
					}
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Removes an attribute
	 *
	 * @param string $strAttriubte [key]
	 * @category iXML
	 */
	public function removeAttribute($strAttriubte) {
		unset($this->arrAttributes[$strAttriubte]);
	}

	/**
	 * Re-Builds the indexes of all sub-nodes
	 *
	 */
	public function reindex() {
		$count = sizeof($this->arrChildren);
		$this->arrChildren = array_values($this->arrChildren);
	    for ($i = 0 ; $i < $count ; $i++)
			$this->arrChildren[$i]->setIndex($i);
	}

	/**
	 * Removes the current node from the parent element (without terminating the node)
	 *
	 * @return Xml
	 */
	public function detach() {
		if (is_null($this->objParent))
			return $this;

		$this->objParent->detachChild($this);
		return $this;
	}

	/**
	 * Detach the specified child form the document tree (without terminating the node)
	 *
	 * @param Xml|int $mxtChild Either the child object or index
	 * @return Xml
	 */
	public function detachChild($mxtChild) {
		if ($mxtChild instanceof Xml)
			$mxtChild = array_search($mxtChild, $this->arrChildren);

		if (is_int($mxtChild) && isset($this->arrChildren[$mxtChild])) {
			unset($this->arrChildren[$mxtChild]);

			$this->reindex();
		}

		return $this;
	}

	// ============= Inserting functions =============

	/**
	 * Inserts a new node AFTER the current node (on the same level).
	 * The function will reindex the current branch after the inserting operation
	 *
	 * @param object $xmlNode [node]
	 * @return bool
	 * @category iXML
	 */
	public function insertAfter($xmlNode) {
		$xmlParent = $this->parent();
		$xmlParent->addChild($xmlNode, $this->index() + 1);
		$xmlParent->reindex();
	}

	/**
	 * Inserts a new node BEFORE the current node (on the same level)
	 * The function will reindex the current branch after the inserting operation
	 *
	 * @param object $xmlNode [node]
	 * @return bool
	 * @category iXML
	 */
	public function insertBefore($xmlNode) {
		$xmlParent = $this->parent();
		$xmlParent->addChild($xmlNode, $this->index());
		$xmlParent->reindex();
	}

	// ============= Debugging functions =============

	/**
	 * Returns the XML code of the tree as string
	 *
	 * @param int $intStructure 0: The XML string will be forced into one single line; 1: The XML string is created without additional tabs for content; 2: Insert additional tabs to pretty up the structure
	 * @param bool $intEncode 0: No enconding of the tag's content; 1: encode special XML-chars (default); 2: add CDATA tags;
	 * @param int $numLevel Number of tabs before the current tag
	 * @return string
	 * @category iXML/Wrap
	 */
	public function toString($intStructure=2, $intEncode=1, $numLevel=0) {
		$intSubStructure = $intStructure;

		// Open the tag
		$strTab = str_repeat("\t", $numLevel);
		$strString = $strTab."<".$this->strTag;

		if (sizeof($this->arrAttributes) > 0) {
			$strString .= " ";
			$arrKeys = array_keys($this->arrAttributes);
			$arrAttr = array();
			for ($z = 0 ; $z < sizeof($arrKeys) ; $z++)
				$arrAttr[] = $arrKeys[$z]."=\"".$this->xmlChars($this->arrAttributes[$arrKeys[$z]])."\"";
			$strString .= implode(' ', $arrAttr);
		}
		if ($this->hasContent()) {
			$strString .= '>';
			if ($this->hasValue() || $this->hasCdata()) {
				$strContent = $this->strStripInline($this->strValue, 0);
				if ($intEncode == 1)
					$strContent = $this->xmlChars($strContent);
				foreach ($this->arrContent as $content) {
					if ($content[0] == 0) {
						$strNode = '';
						if (substr($this->arrCdata[$content[1]], 0, 1) == ' ')
							$strNode .= ' ';
						$strNode .= $this->strStripInline($this->arrCdata[$content[1]], 0);
						if (substr($this->arrCdata[$content[1]], -1) == ' ')
							$strNode .= ' ';
						if ($intEncode == 1)
							$strContent .= $this->xmlChars($strNode);
						else
							$strContent .= $strNode;
					} else {
						$strContent .= trim($this->strStripInline($this->arrChildren[$content[1]]->toString($intStructure, $intEncode, $numLevel+1), 0));
					}
				}

				if ($intStructure == 0)
					$strContent = $this->strStripInline($strContent);
				elseif ($intStructure == 2 && strpos($strContent, "\n") !== false) {
					$arrContent = explode("\n", $strContent);
					$strContent = '';
					foreach ($arrContent as $strLine)
						$strContent .= "\n".$strTab."\t".trim($strLine);
					$strContent = trim($strContent);
				}

				if ($intEncode == 2 && $this->hasContent())
					$strContent = '<![CDATA[ '.$strContent.']]>';

				$strString .= $strContent;
			} else {
				if ($intStructure > 0) $strString .= "\n";
				foreach ($this->arrChildren as $child)
					$strString .= $child->toString($intStructure, $intEncode, $numLevel+1);
				if ($intStructure > 0) $strString .= $strTab;
			}
			$strString .= '</'.$this->strTag.'>';
		} else {
			if (in_array($this->strTag, self::$OPEN_TAGS))
				$strString .= '></'.$this->strTag.'>';
			else
				$strString .= " />";
		}
		if ($intStructure > 0)
			$strString .= "\n";
		return $strString;
	}

	/**
	 * Returns the single XML tag as string
	 *
	 * @param bool $bolEncode If unset, the function will add CDATA-tags for all tag values rather than encoding the XML special chars.
	 * @return string
	 */
	public function toTag($bolEncode=true) {
		// Count the tabs to structure the levels
		$strTab = "";

		// Open the tag
		$strString = "<".$this->strTag;

		if (sizeof($this->arrAttributes) > 0) {
			$strString .= " ";
			$arrKeys = array_keys($this->arrAttributes);
			for ($z = 0 ; $z < sizeof($arrKeys) ; $z++) {
				$strString .= $arrKeys[$z]."=\"".$this->arrAttributes[$arrKeys[$z]]."\"";
				if ($z < (sizeof($arrKeys)-1))
					$strString .= " ";
			}
		}

		if ($this->strValue || in_array($this->tag(), self::$OPEN_TAGS))
			$strString .= ">".$this->xmlChars($this->strStripInline($this->strValue))."</".$this->strTag.">";
		else
			$strString .= " />";

		return $strString;
	}

	/**
	 * Converts the XML tree to a JSON string
	 *
	 * @param bool $bolEncode Return encoded string
	 * @return string
	 */
	public function toJSON($bolEncode=true) {
		$json = array();
		$json['tag'] = $this->tag();

		if ($this->hasAttributes())
			$json['attributes'] = $this->arrAttributes;

		if ($this->hasAttribute('id'))
			$json['id'] = $this->attribute('id');
		if ($this->hasCdata())
			$json['value'] = $this->dump();
		elseif ($this->hasValue())
			$json['value'] = $this->value();

		if ($this->hasChildren() && !$this->hasCdata()) {
			$arrChildren = array();
			foreach ($this->arrChildren as $child) {
				array_push($arrChildren, $child->toJSON(false));
			}
			$json['children'] = $arrChildren;
		}

		return $bolEncode ? json_encode($json) : $json;
	}

	/**
	 * Displays the structure of the XML data as plain text tree
	 *
	 * @param array $arrSpaces The collection of connector spaces
	 * @param string $strSpacer The indentation character (default: tab)
	 * @param int $intContentIndex The node index
	 * @param int $intElemIndex The element index
	 * @param string $strCdata The node's CDATA value
	 * @return string
	 */
	public function toTree($arrSpaces=array(), $strSpacer="\t", $intContentIndex=0, $intElemIndex=0, $strCdata=false) {
		$s = sizeof($arrSpaces)-1;
		if ($s >= 0) {
			$arrTab = $arrSpaces;
			if ($arrTab[$s] == '  ')
				$arrTab[$s] = ' |';
			$strTab = implode($strSpacer, $arrTab).'------';
		} else
			$strTab = '';

		$strTree = $strTab.'['.$intContentIndex.'|'.$intElemIndex.'] ';

		if ($strCdata)
			return $strTree.'CDATA: "'.$this->strStripInline($strCdata).'"'."\n";

		$strTree .= 'XML: '.$this->strTag." (";
		if (sizeof($this->arrAttributes) > 0) {
			$arrKeys = array_keys($this->arrAttributes);
			$x = sizeof($arrKeys);
			for ($z = 0 ; $z < $x ; $z++) {
				$strTree .= $arrKeys[$z]."=\"".$this->arrAttributes[$arrKeys[$z]]."\"";
				if ($z < ($x-1))
					$strTree .= ", ";
			}
		}
		$strTree .= ")".($this->hasValue() ? ' => "'.trim($this->strValue) : '').'"'."\n";

		$y = sizeof($this->arrContent);
		for ($i = 0 ; $i < $y ; $i++) {
			if ($this->arrContent[$i][0] == 0)
				$strTree .= $this->toTree(
					array_merge($arrSpaces, array(($i == $y) ? '  ' : ' |')),
					$strSpacer, $i, $this->arrContent[$i][1],
					$this->arrCdata[$this->arrContent[$i][1]]
				);
			else
				$strTree .= $this->arrChildren[$this->arrContent[$i][1]]->toTree(
					array_merge($arrSpaces, array(($i == $y) ? '  ' : ' |')),
					$strSpacer, $i, $this->arrContent[$i][1]
				);
		}

		return $strTree;
	}
}
