<?php
namespace Xily;

/**
 * date:format
 * 
 * Formats a date
 * 
 * <pre>
 * <math:format format="STRING">TIMESTAMP</math:format>
 * </pre>
 * 
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 1.1 (2009-05-20)
 * @package \Xily\Beans
 * @copyright Copyright (c) 2009-2010, Peter-Christoph Haider
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @license http://www.xily.info/ Commercial License
 */
class DateFormat extends Bean {
	public function result($xmlData, $intLevel=0) {
		if ($t = trim($this -> dump($xmlData)))
			return date($this -> hasAttribute('format') ? $this -> attribute('format') : 'Y-m-d', $t);
	}
}

?>
