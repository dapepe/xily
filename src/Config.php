<?php
namespace Xily;

/**
 * The Config class stores the general system settings
 *
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 2.0 (2013-11-13)
 * @package Xily
 * @copyright Copyright (c) 2009-2013, Peter-Christoph Haider
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class Config {
	/** @var Config The singelton instance */
	private static $instance = NULL;
	/** @var array Array containing the settings */
	public static $arrSettings = array();
	/** @var array Array containing the log entries */
	public static $arrLog= array();

	/**
	 * Private contructor, so the class can only instatiate itself
	 */
	private function __construct() {}

	/**
	 * Return the current instance
	 *
	 * @return Config
	 */
	public static function getInstance() {
		if (self::$instance === NULL)
			self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Forbid cloning
	 */
	private function __clone() {}

	/**
	 * Loads the configuration form an .ini file
	 *
	 * @param string|array $res Settings file or array
	 * @return bool
	 */
    public static function load($res) {
    	if (is_array($res)) {
    		self::$arrSettings = $res;
    		return true;
    	}

    	if (file_exists($res)) {
    		switch (pathinfo($res, PATHINFO_EXTENSION)) {
    			case 'ini':
		    		$arrIni = parse_ini_file($res, true);
		    		foreach ($arrIni as $sec_name => $sec_value) {
		    			if (is_array($sec_value)) {
		    				foreach ($sec_value as $param_name => $param_value) {
		    					if (is_int($param_value))
		    						$value = (int) $param_value;
		    					elseif (is_float($param_value))
		    						$value = (float) $param_value;
		    					elseif (is_string($param_value))
		    						$value = htmlspecialchars_decode($param_value);
		    					elseif (is_array($param_value)) {
		    						$value = array();
		    						foreach ($param_value as $sub_value)
		    							if (is_string($sub_value))
		    								$value[] = htmlspecialchars_decode($sub_value);
		    							else
		    								$value[] = $sub_value;
		    					}
								self::$arrSettings[strtolower($sec_name.'.'.$param_name)] = $value;
		    				}
		    			} else
		    				self::$arrSettings[strtolower($sec_name)] = $sec_value;
		    		}
		    		return true;
    			case 'json':
    				self::$arrSettings = json_decode(file_get_contents($res), 1);
    				return true;
    			default:
    				throw new Exception('Unsupported file type: '.$file);
    				break;
    		}
    	}
    	return false;
    }

    /**
     * Sets a configuration variable.
     *
     * @param string $name
     * @param mixed $value The value to set the variable to.
     * @return bool
     */
    public static function set($name, $value) {
    	try {
        	self::$arrSettings[$name] = $value;
        	return true;
    	} catch(Exception $e) {
    		return false;
    	}
    }

	/**
	 * Returns a specified item from the object
	 *
	 * @param string $name Attribute name
	 * @param string $type Variable type
	 * @param mixed $default Default value
	 * @return mixed
	 */
	public static function get($name, $type='string', $default=false) {
		$value = isset(self::$arrSettings[$name]) ? self::$arrSettings[$name] : '';

		switch ($type) {
    		case 'int':
      			return is_numeric($value) ? (int) $value : (int) $default;
		    case 'float':
      			return is_numeric($value) ? (float) $value : (float) $default;
		    case 'array':
      			return is_array($value) ? $value : array();
		    case 'bool':
		    	if (is_bool($value))
		    		return $value;
		    	elseif ($value == 0)
		    		return false;
		    	elseif ($value == 1)
		    		return true;
		    	else
		    		return (bool) $default;
			case 'object':
      			return is_object($value) ? $value : null;
  		}

  		return $value === '' ? (string) $default : (string) $value;
	}

	/**
	 * Returns and formats a directory<
	 * @param string $strDirectory The directory
	 * @param string $strSlash The slash format (default is DIRECTORY_SEPARATOR)
	 * @return string The directory name with trailing slash
	 */
	public static function getDir($strDirectory, $strSeparator=DIRECTORY_SEPARATOR) {
		if ($strDirectory = self::get($strDirectory))
			return substr($strDirectory, -1) != $strSeparator ? $strDirectory.$strSeparator : $strDirectory;
		return null;
	}

	/**
	 * Utility function to collect log messages in an array
	 *
	 * @param  string $strSource  Source file
	 * @param  string $strMessage Log message
	 * @param  string $strType Message type
	 * @return void
	 */
	public static function log($strSource, $strMessage, $strType='NOTICE') {
		self::$arrLog[] = array($strSource, $strMessage, $strType);
	}
}

?>
