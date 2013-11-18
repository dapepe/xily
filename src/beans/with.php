<?php
namespace Xily;

/**
 * view:collect \Xily\Bean class
 * 
 * Loads a data source into the temporary data source and passes it to its child nodes
 * 
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 3.0 (2013-03-29)
 * @package Xily
 * @copyright Copyright (c) 2009-2013, Peter-Christoph Haider
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @license http://www.xily.info/ Commercial Licenseial License
 */
class BeanWith extends Bean {
	public function result($xmlData, $intLevel=0) {
		if (!$this->attribute('source'))
			throw new beanException('No source value specified for xily:with');
			
		return $this->dump($this->xdr($this->attribute('source'), $xmlData, 0, 1));
	}
}

?>
