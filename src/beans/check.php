<?php
namespace Xily;

/**
 * Checks if a value is set
 *
 * <check value="{XDR}">
 *   <true>{CDATA}</true>
 *   <false>{CDATA}</false>
 * </check>
 *
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 3.0 (2013-03-29)
 * @package Xily
 * @copyright Copyright (c) 2009-2013, Peter-Christoph Haider
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @license http://www.xily.info/ Commercial License
 */
class BeanCheck extends Bean {
	public function result($xmlData, $intLevel=0) {
		if (!$this->attribute('value'))
			throw new Exception('No check value set for check. Nothing to do.');

		$mxtResult = $this->xdr($this->attribute('value'), $xmlData, 0, 0);

		if ($mxtResult) {
			$xmlTrue = $this->child('true');
			if ($xmlTrue)
				return $xmlTrue->dump($xmlData, $intLevel+1);
			if (!$this->child('false'))
				return $this->dump($xmlData, $intLevel+1);
		} else {
			$xmlFalse = $this->child('false');
			if ($xmlFalse)
				return $xmlFalse->dump($xmlData, $intLevel+1);
		}
	}
}

?>
