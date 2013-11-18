<?php
namespace Xily;

/**
 * Does nothing but dumping the content of the tag.
 * Furthermore, xily:containers can be used to store data and variables inside attributes and datasets.
 *
 * <pre>
 * <container id="{STRING}" attribute1="{STRING}" _dataset="{XDR}" />
 * </pre>
 *
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 3.0 (2013-03-29)
 * @package Xily
 * @copyright Copyright (c) 2009-2013, Peter-Christoph Haider
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @license http://www.xily.info/ Commercial License
 */
class BeanContainer extends Bean {
	public function result($xmlData, $intLevel=0) {
		// Simply do nothing - only dump the content of the tag
		return $this->dump($xmlData);
	}
}

?>
