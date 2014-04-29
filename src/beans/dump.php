<?php
namespace Xily;

/**
 * <dump source="%{XDR}" />
 *
 * Dumps the contents of an XDR
 *
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 3.0 (2013-03-29)
 * @package Xily
 * @copyright Copyright (c) 2009-2013, Peter-Christoph Haider
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @license http://www.xily.info/ Commercial License
 */
class BeanDump extends Bean {
	public function result($xmlData, $intLevel=0) {
		if (!$this->attribute('source'))
			throw new Exception('No source value specified for xily:repeat');

		$mxtData = $this->xdr($this->attribute('source'), $xmlData, 0, 1);

		return '<pre>'.print_r($mxtData, true).'</pre>';
	}
}

?>
