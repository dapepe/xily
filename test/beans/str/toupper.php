<?php
namespace Xily;

/**
 * str:toupper
 *
 * Returns string with all alphabetic characters converted to uppercase.
 *
 * <pre>
 * <str:tolower>STRING</str:tolower>
 * </pre>
 *
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 1.1 (2009-05-20)
 * @package \Xily\Beans
 * @copyright Copyright (c) 2009-2010, Peter-Christoph Haider
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @license http://www.xily.info/ Commercial License
 */
class StrToupper extends Bean {
	public function result($xmlData, $intLevel=0) {
		return strtoupper($this->dump($xmlData));
	}
}

?>
