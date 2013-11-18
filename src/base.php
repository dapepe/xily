<?php
namespace Xily;

/**
 * The Xily Base class provides some basic features for all other Xily classes.
 *
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 2.0 (2013-11-13)
 * @package Xily
 * @copyright Copyright (c) 2009-2013, Peter-Christoph Haider
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class Base {
	/**
	 * Initialize a variable value
	 *
	 * @param int|float|array|bool|object|string $mxtValue Value to initialize
	 * @param string $strType Variable type
	 * @param int|float|array|bool|object|string $mxtDefault Default value
	 * @return int|float|array|bool|object|string Initialized value
	 */
	public static function initValue($mxtValue, $strType='string', $mxtDefault='') {
		switch ($strType) {
    		case 'int':
      			return is_numeric($mxtValue) ? (int) $mxtValue : (int) $mxtDefault;
		    case 'float':
      			return is_numeric($mxtValue) ? (float) $mxtValue : (float) $mxtDefault;
		    case 'array':
      			return is_array($mxtValue) ? $mxtValue : array();
		    case 'bool':
		    	return is_bool($mxtValue) ? $mxtValue : (bool) $mxtDefault;
			case 'object':
      			return is_object($mxtValue) ? $mxtValue : null;
  		}
  		return $mxtValue === '' ? (string) $mxtDefault : $mxtValue;
	}

	/**
	 * Initializes a request variable
	 *
	 * @param string $strKey Name of the variable
	 * @param mixed $mxtDefault Default value
	 */
	function initRequest($strKey, $mxtDefault=null) {
		if (isset($_REQUEST[$strKey]))
			return get_magic_quotes_gpc() ? stripSlashesRecursive($_REQUEST[$strKey]) : $_REQUEST[$strKey];
		else
			return $mxtDefault;
	}

	/**
	 * Creates an array containing the tree parameters attribute, operator and value to
	 * express an equation. The method also aims to simplify the operator by narrowing them down to <, >, =, and !
	 *
	 * @todo Check multiple equations and include equation operators (&&, ||, !)
	 * @param string $strFilter The filter string, e.g "x >= 10"
	 * @param bool $bolList Returns a list of all equations
	 * @return array ['attribute', 'operator', 'value']
	 */
	public static function equationBuild($strFilter, $bolList=false) {
		// Initiailize the operators
		$arrOperator = array('==', '!=', '>=', '<=', '>', '<');

		preg_match_all('/\s*(?:"(.*?)"|\'(.*?)\'|([a-zA-Z_0-9.:%\{\}@\[\]#-]+))\s*(==|!=|>=?|<=?)\s*(?:"(.*?)"|\'(.*?)\'|([a-zA-Z_0-9.:%\{\}@\[\]#-]+))\s*/', $strFilter, $arrQuot, PREG_SET_ORDER);

		$arrResult = array();
		if ($arrQuot) {
			foreach ($arrQuot as $arrMatch) {
				$operator = $arrMatch[4];
				$value = array_pop($arrMatch);
				array_shift($arrMatch);
				$attribute = '';
				while ($arrMatch && $attribute == '')
					$attribute = array_shift($arrMatch);
				$arrEquation = array(
					'attribute' => $attribute,
					'value' => $value,
					'operator' => $operator
				);
				if (!$bolList)
					return $arrEquation;
				$arrResult[] = $arrEquation;
			}

		}

		return $arrResult;
	}

	/**
	 * Checks, if an equation is correct
	 *
	 * @param string $strValue1 First parameter
	 * @param string $strValue2 Second parameter
	 * @param string $strOperator Equation operator (==, !=, <, >, <=, >=)
	 * @return bool
	 */
	public static function equationCheck($strValue1, $strValue2, $strOperator='==') {
		// $this -> probe('equationCheck', 'Checking: '.$strValue1.' '.$strOperator.' '.$strValue2, 3);
		switch ($strOperator) {
			case '!':
			case '!=':
				return $strValue1 != $strValue2;
			case '<':
				return $strValue1 < $strValue2;
			case '>':
				return $strValue1 > $strValue2;
			case '<=':
				return $strValue1 <= $strValue2;
			case '>=':
				return $strValue1 >= $strValue2;
			default:
				return $strValue1 == $strValue2;
		}
		return false;
	}

	// ============== Formating functions ==============

	/**
	 * Strips the quote symbols form a string
	 *
	 * @param string $strQuote
	 * @return string
	 */
	public static function strStripQuotes($strQuote) {
		return Base::strStripString(Base::strStripString($strQuote, "'"), '"');
	}

	/**
	 * Strips the brackets from the beginning and end of a string
	 *
	 * @param string $strQuote
	 * @return string
	 */
	public static function strStripBrackets($strQuote) {
		$strText = trim($strQuote);
		$arrOpen = array('{', '[', '(');
		$arrClose = array('}', ']', ')');
		if (in_array(substr($strText, 0, 1), $arrOpen)) {
			$strText = substr($strText, 1);
			if (in_array(substr($strText, -1), $arrClose))
				$strText = substr($strText, 0, -1);
		}
		return $strText;
	}

	/**
	 * Strips defined chars from the beginning and the end of a string
	 *
	 * @param string $strText
	 * @param string $strOpen
	 * @param string $strClose
	 * @param bool $bolStrict Only strip symmetrical strings
	 * @return string
	 */
	public static function strStripString($strText, $strOpen, $strClose="", $bolStrict=false) {
		if (!$strClose)
			$strClose = $strOpen;

		$strNew = $strText;
		$bolStart = false;
		if (substr($strText, 0, strlen($strOpen)) == $strOpen) {
			$strNew = substr($strNew, strlen($strOpen));
			$bolStart = true;
		}

		if (substr($strText, -strlen($strClose)) == $strClose)
			if (($bolStrict && $bolStart) || !$bolStrict)
				return substr($strNew, 0, -strlen($strClose));

		return $strText;
	}

	/**
	 * Strips all whitespaces from the beginning and end of a string
	 *
	 * @param string $strText
	 * @return string
	 */
	public static function strStripWhitespaces($strText) {
		$arrWhitespaces = array(" ", "\n", "\r", "\t", "\0", "\x0B");
		while (in_array(substr($strText, 0, 1), $arrWhitespaces) || in_array(substr($strText, -1), $arrWhitespaces))
			$strText = trim($strText);
		return $strText;
	}

	/**
	 * Reduces a paragraph to one single line, removing all linebreaks, tabs and blank spaces
	 *
	 * @param string $strText
	 * @param bool $bolLineBreaks If set, also linebreaks are removed (otherwise only tabs and blank spaces)
	 * @return string
	 */
	public static function strStripInline($strText, $bolLineBreaks=true) {
		$arrWhitespaces = array("\t", "\x0B", "\0");
		if ($bolLineBreaks)
			$arrWhitespaces = array_merge($arrWhitespaces, array("\n", "\r"));
		$strText = str_replace($arrWhitespaces, ' ', $strText);
		$i = 1;
		while ($i > 0)
			$strText = str_replace("  ", " ", $strText, $i);
		return $strText;
	}

	/**
	 * Shortens a string to a specified number of chars.
	 *
	 * @param string $strText
	 * @param int $numLength
	 * @param bool $bolDots Puts three dots [...] at the end of the shortened string.
	 * @param bool $bolWords Respect single words when shortening a text.
	 * @return string
	 */
	public static function strShorten($strText, $numLength, $bolDots=false, $bolWords=false) {
		if (strlen($strText) > $numLength) {
			if ($bolDots && strlen($strText) > 6) {
				$numLength = $numLength - 6;
				$strDots = " [...]";
			}
			$strText = substr($strText, 0, $numLength);
			if ($bolWords) {
				for ($i = $numLength-1 ; $i > 0 ; $i--)
					if (substr($strText, $i, 1) == " ")
						break;
				if ($i > 0)
					$strText = substr($strText, 0, $i);
			}
			return $strText.($bolDots ? $strDots : '');
		} else {
			return $strText;
		}
	}

	/**
	 * Converts a formated string into a float
	 *
	 * @param string $strNumber The formated number, e.g. "1,293.00 €"
	 * @param bool $bolNegative Support negative numbers
	 * @return float
	 */
	public static function strToFloat($strNumber, $bolNegative=true) {
		$strNumber = preg_replace('/[^0-9,.'.($bolNegative ? '-' : '').']+/', '', $strNumber);

		// Case 1: Format is 1,000.00
		// Case 2: Format is 1.000,00
		if (strpos($strNumber, '.') > strpos($strNumber, ','))
			return str_replace(',', '', $strNumber);
		else
			return str_replace(array('.', ','), array('', '.'), $strNumber);
	}

	/**
	 * Converts a HTML string into a plain text string, decoding existing HTML special chars
	 *
	 * @param string $strHTML
	 * @param bool $bolBreak Also replace <br /> linebreaks in regular linebreaks "\n"
	 * @return string
	 */
	public static function htmlDecode($strHTML, $bolBreak=true) {
		return html_entity_decode(
			preg_replace(
				array('/&#([0-9]+);/e', '/&#x([0-9a-zA-Z]+);/e'),
				array('chr(\'$1\');','chr(hexdec(\'$1\'));'),
	      		strip_tags($bolBreak ? preg_replace('/<br\s*\/?\s*>/i', "\n", $strHTML) : $strHTML)
	    	),
	    	ENT_QUOTES,
	    	"UTF-8"
	  	);
	}

	/**
	 * Converts a regular string into HTML code, encoding HTML special chars.
	 *
	 * @param string $strText
	 * @param string $bolBreak If linebreak is true, the function will add <br /> linebreaks
	 * @return unknown
	 */
	public static function htmlEncode($strText, $bolBreak=true) {
		$strText = htmlentities($strText);
		if ($bolBreak)
			$strText = preg_replace('/\r?\n/', '<br />', $strText);
		return $strText;
	}

	// ============== Additional XML-functions ==============

	/**
	 * Encodes special XML characters
	 *
	 * @param string $strRaw
	 * @return string
	 */
	public static function xmlChars($strRaw) {
		$arrRaw = array('&', '<', '>', '"', '\'', 'Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß');
		$arrEncoded = array('&amp;', '&lt;', '&gt;', '&quot;', '&#39;', '&#196;', '&#214;', '&#220;', '&#228;', '&#246;', '&#252;', '&#223;');
		return str_replace($arrRaw, $arrEncoded, $strRaw);
	}

	/**
	 * Creates a unique identifier
	 *
	 * @return string
	 */
	public static function createUID() {
		return md5(uniqid(microtime()));
	}

	// ============== File functions ==============

	/**
	 * Makes sure the directory ends with a slash
	 *
	 * @param string $strDirectory The directory
	 * @param string $strSlash The slash format (default is DIRECTORY_SEPARATOR)
	 * @return string
	 */
	public static function fileFormatDir($strDirectory, $strSlash=DIRECTORY_SEPARATOR) {
		return substr($strDirectory, -1) != $strSlash ? $strDirectory.$strSlash : $strDirectory;
	}

	/**
	 * Reads the contents of a file to a string
	 *
	 * @param string $strFile The filename
	 * @return string|bool
	 */
	public static function fileRead($strFile) {
		if (is_readable($strFile))
			return file_get_contents($strFile);
		else
			return false;
	}

	/**
	 * Reads the contents of a file to a string line by line
	 * and optionally calls a function for each line
	 *
	 * @param string $strFile The filename
	 * @param stirng $strFunction Name of the function to be called for each line
	 * @return string The file contents
	 */
	public static function fileReadByLine($strFile, $strFunction=false) {
		if (file_exists($strFile) && is_readable($strFile)) {
			$fh = fopen($strFile, 'r');
			$strData = "";
			while (!feof($fh)) {
				$strBuffer = fgets($fh, 4096);
				if ($strFunction)
					$strData .= call_user_func($strFunction, trim($strBuffer));
				else
					$strData .= $strBuffer;
			}
			fclose($fh);
			return $strData;
		} else
			return false;
	}

	/**
	 * Writes a string to a file
	 *
	 * @param string $strFile The filename
	 * @param string $strText String to write
	 * @param bool $bolModeAdd Appends the string at the end of the file
	 * @return bool
	 */
	public static function fileWrite($strFile, $strText, $bolModeAdd=false) {
		$fh = fopen($strFile, $bolModeAdd?'a':'w');
		$bolStatus = fwrite($fh, $strText);
		fclose($fh);
		return $bolStatus;
	}

	/**
	 * Recursively removes a directory and the files and subdirectories contained in it
	 *
	 * @param string $strDirectory
	 * @param bool $bolEmpty Declaration of the directory to be empty
	 * @return bool
	 */
	public static function fileRemoveDir($strDirectory, $bolEmpty=false) {
		// Remove the "/" at the end of the directory, if existent
		if (in_array(substr($strDirectory, -1), array('/', DIRECTORY_SEPARATOR)))
			$strDirectory = substr($strDirectory, 0, -1);

		if (!file_exists($strDirectory) || !is_dir($strDirectory)) {
			// Check if the path is not valid or is not a directory
			return false;
		} elseif (!is_readable($strDirectory)) {
			// Check if the path is readable
			return false;
		} else {
			// Open the directory handler and scan through the items inside
			$handle = opendir($strDirectory);
			while (false !== ($item = readdir($handle))) {
				if ($item != '.' && $item != '..') {
					// Build the path of the item (file or directory) to delete
					$path = $strDirectory.'/'.$item;
					// Check, if the item is a directory or a file
					if (is_dir($path))
						Base::fileRemoveDir($path);
					else
						unlink($path);
				}
			}
			closedir($handle);

			// Check if the directory is declared empty
			if ($bolEmpty == false) {
				// Try to delete the now empty directory
				if (!rmdir($strDirectory))
					return false;
			}
			return true;
		}
	}

	/**
	 * Recursively copies a directory and the files and subdirectories contained in it
	 *
	 * @param string $strDirectory
	 * @param string $strDestination
	 * @param bool $bolCreate This option defines, if the whole directory should be copied or only its contents
	 * @return bool
	 */
	public static function fileCopyDir($strDirectory, $strDestination, $bolCreate=true) {
		// Initialize the result variable
		$bolResult = true;
		// Remove the "/" at the end of the directory, if existent
		$strDirectory = Base::fileFormatdir($strDirectory);
		$strDestination = Base::fileFormatdir($strDestination);
		if (file_exists($strDirectory) || is_dir($strDirectory) || is_readable($strDirectory)) {
			// Should the function copy the whole directory or only its content? (standard: content only)
			$arrPath = pathinfo($strDirectory);
			if ($bolCreate) {
				$strDestination = $strDestination.$arrPath['basename'].'/';
				if (!file_exists($strDestination))
					@mkdir($strDestination);
			}
			$handle = opendir($strDirectory);
			while (false !== ($item = readdir($handle))) {
				if ($item != '.' && $item != '..' && $item != '.svn' && $bolResult) {
					// Check, if the item is a directory or a file
					if (is_dir($strDirectory.$item))
						$bolResult = self::fileCopyDir($strDirectory.$item, $strDestination);
					else
						$bolResult = copy($strDirectory.$item, $strDestination.$item);
				}
			}
			closedir($handle);
			return $bolResult;
		} else
			return false;
	}

	/**
	 * Returns an array with the contents of a directory
	 *
	 * @param string $strDirName The directory name
	 * @param string $strFiletype Filter for certain file extensions
	 * @return array
	 */
	public static function fileListDir($strDirName, $strFiletype='') {
		$arrDirContent = array();
		$strDirName = self::fileFormatDir($strDirName);
		if (is_dir($strDirName) && $handle = opendir($strDirName)) {
			while ($strFile = readdir($handle))
				if ($strFile != '.' && $strFile != '..' && $strFile != '.svn'
					&& ($strFiletype == ''
						|| self::fileGetExtension($strFile) == $strFiletype
						|| ($strFiletype == -1 && is_dir($strDirName.$strFile))
						)
					)
					$arrDirContent[] = basename($strFile);
			closedir($handle);
			return $arrDirContent;
		} else
			return array();
	}

	/**
	 * Returns the file extension
	 *
	 * @param string $strFilename Filename
	 * @return string
	 */
	public static function fileGetExtension($strFilename) {
		$split = explode('.', $strFilename);
		if (sizeof($split) > 0)
			return array_pop($split);
		else
			return '';
	}

	/**
	 * Renames a file
	 *
	 * @param string $strOldFileName
	 * @param string $strNewFileName
	 * @return bool
	 */
	public static function fileRename($strOldFileName, $strNewFileName) {
		return is_writeable($strOldFileName) && rename($strOldFileName, $strNewFileName);
	}

	// ============== General debugging functions ==============

	/**
	 * Echos a message, if modeDebug is active. This function can be very useful to place status messages
	 * in your functions, to gain some insights of what your program is doing.
	 *
	 * @param string $strSource The function's name where the probe is placed
	 * @param string $strMessage The debug message
	 * @param int $intLevel Priority/Level of the message
	 * @return bool Returns, whether the probe was echoed
	 */
	public function probe($strSource, $strMessage, $intLevel=0) {
		if (Config::get('debug.console', 'bool', false)) {
			// Check, if the class is not excluded from debugging
			if (!in_array(get_class($this), Config::get('debug.exclude', 'array', array()))) {
				$strArrow = str_repeat('-', $intLevel).'> ';
				echo ($this -> strTag ? '['.$this -> strTag.($this -> hasAttribute('id') ? '/'.$this -> id() : '').']' : '')
					.$strArrow.$strSource.': '.$strMessage."<br/>\n";
				return true;
			}
		}
		return false;
	}
}

?>
