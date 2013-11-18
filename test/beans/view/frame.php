<?php
namespace Xily;

/**
 * view:frame \Xily\Bean class
 *
 * The xily:frame class helps you to create individual views for your application
 * by using the xilyFrame class to display you application inside an individual
 * HTML frame.
 *
 * <pre>
 * <view:frame id="{STRING}" _frame="{XDR}" file="{STRING}">
 * 		<section target="{STRING}">
 *
 * 		</section>
 * 		<section ... />
 * </view:frame>
 * </pre>
 *
 * Usage:
 *  - Define the desired xilyFrame by using the _frame dataset or
 *    by defining a local file.
 *  - Add <section> tags to add content to your xilyFrame sections
 *  - Some standard sections are predefined for collectors:
 *    * css: Section for the CSS collector
 *    * js: Section for the JavaScript collector
 *    * jsinit: Section for JavaScript, which is called by an event listeneer
 *              after the window is loaded. Example for mootools:
 *              window.addEvent('domready',function() { {sec:jsinit} });
 *
 *
 * @author Peter-Christoph Haider (Project Leader) et al. <info@xily.info>
 * @version 1.0
 * @package \Xily\Beans
 * @copyright Copyright (c) 2008, Peter Haider
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @license http://www.zeyon.net/ Commercial License (contact our sales staff)
 *
 */
class ViewFrame extends Bean {
	public $colCSS;
	public $colJS;
	public $colJSInit;

	public function result($xmlData, $intLevel=0) {
		$xlyTemplate = new Dict();

		if ($this->hasDataset('frame'))
			$strTemplate = $this->hasDataset('frame');
		elseif ($this->hasAttribute('file') && is_readable($this->attribute('file')))
			$strTemplate = $this->fileRead($this->attribute('file'));
		else
			throw new beanException('No Template specified for view:frame');

		$arrSection = $this->children('section');
		foreach ($arrSection as $section)
			if ($section->hasAttribute('name'))
				$xlyTemplate->set($section->attribute('name'), $section->dump());

		// Insert the blocks into the template and return the result
		return $xlyTemplate->insertInto($strTemplate);
	}
}

?>
