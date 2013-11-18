<?php
namespace Xily;

/**
 * Does nothing but dumping the content of the tag.
 * Furthermore, xily:containers can be used to store data and variables inside attributes and datasets.
 *
 * <code>
 * <if condition="value1<value2">
 * 	<then>
 * 		[Subdata]
 *	</then>
 *	<else>
 *		[Subdata]
 *	</else>
 * </if>
 * </code>
 *
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 3.0 (2013-03-29)
 * @package Xily
 * @copyright Copyright (c) 2009-2013, Peter-Christoph Haider
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @license http://www.xily.info/ Commercial License
 */
class BeanIf extends Bean {
	public function result($xmlData, $intLevel=0) {
		if (!$this->attribute('condition'))
			throw new beanException('Attribute "condition" not specified for <if>');

		if (!$arrEuquation = $this->equationBuild($this->attribute('condition')))
			throw new beanException('An error has been detected in the following condition: '.$condition);

		$strAttribute = $this->xdrInsert($arrEuquation['attribute'], $xmlData);
		$strValue = $this->xdrInsert($arrEuquation['value'], $xmlData);
		if ($this->equationCheck($strAttribute, $strValue, $arrEuquation['operator'])) {
			if ($xmlThen = $this->children('then'))
				return $xmlThen[0]->dump();
			else
				return $this->dump();
		} else {
			if ($xmlElse = $this->children('else'))
				return $xmlElse[0]->dump();
		}

		if ($this->equationCheck($strAttribute, $strValue, $arrFilter['operator']))
			return $this->dump($xmlData, $intLevel);

		return false;
	}
}

?>
