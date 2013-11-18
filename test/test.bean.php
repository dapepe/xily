<?php

namespace Xily;

header('Content-type: text/plain; charset=UTF-8');

include dirname(__FILE__).'/../src/config.php';
include dirname(__FILE__).'/../src/base.php';
include dirname(__FILE__).'/../src/dict.php';
include dirname(__FILE__).'/../src/xml.php';
include dirname(__FILE__).'/../src/bean.php';

// First of, you can define custom bean directories for your bean classes. Xily will try to automatically include those classes when a certain bean is required.
Bean::$BEAN_DIRS = array(
	dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'beans',
	dirname(__FILE__).DIRECTORY_SEPARATOR.'beans',
);

// The following example uses Xily's <repeat/> bean to repeat a given data path and provide use each element
// as temporary dataset for all subsequent tags
$source = '<?xml version="1.0" encoding="UTF-8"?>
<html>
	<repeat source="#{.get(http://feeds.bbci.co.uk/news/technology/rss.xml)->xml->channel.item}">
		<div>
			<h3>#{title}</h3>
			<p>#{description}</p>
			<a href="#{link}">Read all</a>
		</div>
	</repeat>
</html>';

$xlyDoc = Bean::create($source);
echo $xlyDoc->run();


// Let's take a look at another exmple
class FormFrame extends Bean {
	public function result($xmlData, $intLevel=0) {
		if ($this->hasAttribute('left')) {
			if ($xmlData instanceof XML)
				$xmlData->setAttribute('left', $this->attribute('left'));
			else
				$xmlData = new XML('data', null, array('left' => $this->attribute('left')));
		}

		return '<form class="form-horizontal" role="form"'
				.($this->hasAttribute('id') ? ' id="'.$this->attribute('id').'"' : '')
				.($this->hasAttribute('action') ? ' action="'.$this->attribute('action').'"' : '')
				.($this->hasAttribute('target') ? ' target="'.$this->attribute('target').'"' : '')
				.($this->hasAttribute('style') ? ' style="'.$this->attribute('style').'"' : '')
				.'>'
				.$this->dump($xmlData, $intLevel+1)
				.'</form>';
	}
}

class FormField extends Bean {
	public function result($xmlData, $intLevel=0) {
		$widthLeft = 2;
		if ($this->hasAttribute('left'))
			$widthLeft = (int) $this->attribute('left');
		elseif ($xmlData instanceof XML && $xmlData->hasAttribute('left'))
			$widthLeft = (int) $xmlData->attribute('left');

		$widthRight = 12 - $widthLeft; // Bootstrap's grid system is based on 12 columns

		$id   = $this->attribute('id');
		$name = $this->attribute('name');

		if (!$id || $id == '') {
			if (!$name || $name == '')
				return; // Either a name or an ID will have to be specified

			$id = 'txt'.ucfirst($name); // Automatically create an ID based on the name
		} elseif (!$name || $name == '') {
			$name = $id;
		}

		$elem = '<div class="form-group">'
				.'<label for="'.$id.'" class="col-sm-'.$widthLeft.' control-label">'
				.$this->attribute('label')
				.($this->isTrue('required') ? '<span class="glyphicon glyphicon-asterisk" />' : '')
				.'</label>'
				.'<div class="col-sm-'.$widthRight.'">';

		if ($this->hasChildren()) {
			$elem .= '<select class="form-control" id="'.$id.'" name="'.$name.'">'
					.($this->hasAttribute('placeholder') ? '<option value="">'.$this->attribute('placeholder').'</option>' : '');

			foreach ($this->children() as $xmlChild) {
				if (!$xmlChild->hasAttribute('value'))
					$xmlChild->setAttribute('value', trim($xmlChild->value()));
				if ($this->hasAttribute('selected') && $this->attribute('selected') == $xmlChild->attribute('value'))
					$xmlChild->setAttribute('selected', 'selected');

				$elem .= $xmlChild->toString();
			}

			$elem .= '</select>';
		} elseif ($this->attribute('type') == 'multi') {
			$elem .= '<textarea class="form-control" name="'.$id.'" name="'.$name.'" rows="'.($this->hasAttribute('rows') ? $this->attribute('rows') : 3).'"></textarea>';
		} else {
			$elem .= '<input type="'.($this->hasAttribute('type') ? $this->attribute('type') : 'text').'"'
					.($this->hasAttribute('placeholder') ? ' placeholder="'.$this->attribute('placeholder').'"' : '')
					.' class="form-control" name="'.$id.'" name="'.$name.'" />';
		}

		return $elem.'</div></div>';
	}
}

$xlyDoc = Bean::create('<?xml version="1.0" encoding="UTF-8"?>
<html>
	<form:frame width="2">
		<form:field name="Firstname" label="First name" required="true" />
		<form:field name="Lastname" label="Last name" required="true" />
		<form:field name="Email" label="E-mail" type="email" />
		<form:field name="region" label="Region?" placeholder="- Please Select -">
			<option>North America</option>
			<option>Central America</option>
			<option>South America</option>
			<option>European Union</option>
			<option>Eastern Europe</option>
			<option>Africa</option>
			<option>Middle East</option>
			<option>Asia</option>
			<option>Oceania</option>
			<option>The Caribbean</option>
		</form:field>
	</form:frame>
</html>');

echo $xlyDoc->run();
