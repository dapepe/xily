<?php
namespace Xily;

/**
 * Calls a PHP function to retrieve some data
 *
 * <pre>
 * <call id="{STRING}" function="{STRING}" convert="{BOOL}" store="{BOOL}" class="{STRING}">
 * 	<param>value</param>
 *  ...
 * </call>
 * </pre>
 *
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 3.0 (2013-03-29)
 * @package Xily
 * @copyright Copyright (c) 2009-2013, Peter-Christoph Haider
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @license http://www.xily.info/ Commercial License
 */
class BeanCall extends Bean {
	public function result($xmlData, $intLevel=0) {
		if ($this->hasAttribute('function')) {
			$arrParams = array();
			$xlsParams = $this->children('param');
			foreach ($xlsParams as $xmlParam)
				$arrParams[] = $this->xdrInsert($xmlParam->value());

			$class = $this->attribute('class');
			$function = $this->attribute('function');
			if ( (string)$class === '' ) {
			} elseif ( $this->isTrue('instantiate') ) {
				$instance = new $class();
				$function = array($instance, $function);
			} else {
				$function = array($class, $function);
			}

			$res = call_user_func_array($function, $arrParams);

			if ($this->isTrue('convert')) {
				$xmlNode = new XML($this->attribute('function'));
				$res = $this->convertXML($xmlNode, $res);
			}

			if ($this->isTrue('store')) {
				$this->setDataset($res);
			} else
				return $res;
		}
	}

	/**
	 * Check, if the array is associative
	 *
	 * @param bool $a
	 */
	private function checkAssoc($a) {
		foreach(array_keys($a) as $key)
			if (is_int($key))
				return false;
		return true;
	}

	/**
	 * Converts the result into an XML structure
	 *
	 * @param \Xily\Xml $xmlNode
	 * @param string|array $mxtData
	 */
	public function convertXML($xmlNode, $mxtData) {
		if (is_array($mxtData)) {
			if (!$this->isFalse('assoc') && $this->checkAssoc($mxtData)) {
				foreach ($mxtData as $key => $value) {
					$xmlChild = new XML($key);
					$xmlNode->addChild(self::convertXML($xmlChild, $value));
				}
			} else {
				foreach ($mxtData as $key => $value) {
					$xmlChild = new XML('node', null, array('key' => $key));
					$xmlNode->addChild(self::convertXML($xmlChild, $value));
				}
			}
		} else
			$xmlNode->setValue($mxtData);

		return $xmlNode;
	}
}

?>
