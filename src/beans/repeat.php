<?php
namespace Xily;

/**
 * xily:container
 *
 * Does nothing but dumping the content of the tag.
 * Furthermore, xily:containers can be used to store data and variables inside attributes and datasets.
 *
 * <pre>
 *	<repeat source="{XDR}" />
 * 		[Subdata {XDR}]
 * 	</repeat>
 * </pre>
 *
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 3.0 (2013-03-29)
 * @package Xily
 * @copyright Copyright (c) 2009-2013, Peter-Christoph Haider
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @license http://www.xily.info/ Commercial License
 */
class BeanRepeat extends Bean {
	public function result($xmlData, $intLevel=0) {
		if (!$this->attribute('source'))
			throw new Exception('No source value specified for xily:repeat');

		$mxtData = $this->xdr($this->attribute('source'), $xmlData, 0, 1);

		if ($mxtData instanceof XML || $mxtData instanceof Bean)
			$mxtData = array($mxtData);

		$strResult = '';
		if (is_array($mxtData)) {
			$this->setDataSet($xmlData);
			$count  = 0;
			$offset = $this->hasAttribute('offset')? (int) $this->attribute('offset') : 0;
			$step   = $this->hasAttribute('step') ? (int) $this->attribute('step') : 1;
			foreach ($mxtData as $key => $value) {
				$count++;
				if (!is_int(($count - $offset) / $step))
					continue;

				// Build an array for array values
				if (!$value instanceof XML)
					$value = array('key' => $key, 'value' => $value);

				$this->setDataSet($value, 'temp');
				$mxtResult = implode('', $this->runChildren($value, $intLevel));
				if (is_string($mxtResult))
					$strResult .= $mxtResult;
			}
			$this->clearDataSet();
		}
		return $strResult;
	}
}

?>
