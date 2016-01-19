Xily - An XML processing and templating library for PHP
=======================================================

Purpose
-------
Xily is a leight-weight PHP framework to develop XML-based applications. The framework contains five core classes, which enable working. One major aspect is the representation of XML data in an DOM-like object model. Based on this model you can use Xily to add individual parsers (xilyBeans) for each XML tag, which enables developers to create modular, XML-driven web-applications.


A bit of history
----------------

Xily has been developed by [Peter Haider](https://github.com/dapepe) at [ZeyOS](http://www.zeyos.com). The development started in 2008 when the team was in need for a framework to develop websites and portals. At the time we were looking at a lot of XML-based based templating systems such as Adobe ColdFusion, Open Laszlo or Adobe Flex. The approach to use XML to describe part of the the front-end really appealed to us and we figured that we would like to have something similar for PHP. And since every project might have different requirements it would be nice to extend the XML engine with new behaviour and tags.

The result of all this was Xily, a Framework which enabled us to (a.) process XML documents in PHP and (b.) to develop completely XML-driven applications and websites, by simply encapsulating complex objects and program logic in single objects called Xily Beans.


Why Xily?
---------

### Lightweight ###
Xily is focused around it’s core features – which is utilizing XML. The core library consists of 5 files in total!

### Focused ###
Xily serves one purpose only: To create XML-driven apps in PHP. You can use the xilyXML library to simply process XML documents, while xilyBeans enable you to create XML-driven applications and websites by developing your own set of XML-commands.

### Extensible ###
You can extend and enhance Xily as you go. Xily doesn’t force any specific way of doing things on you – you can even create your own set of XML commands.


Main components
---------------

### Xily XML ###

The `XML` class is being used to work with XML structures. It can be used to parse an XML file/string and to work dynamically with this information or to manipulate the XML structure.


### Xily Bean ###

The `Bean` class allows you to attach application logic to specific XML tags, this means you can practically define your custom XML command palette.


### Xily Dictionary ###

The `Dictionary` class provides an interface between XML and Array, so that Arrays can be treated like an XML tree within the `Bean` class.


### Xily Config ###

The `Config` class is a [singleton](http://en.wikipedia.org/wiki/Singleton_pattern) providing access to global configuration settings.


### Xily Base ###

The `Base` class provides basic utility methods shared by the `Dict`, `XML` and `Bean` class


Tests and examples
------------------

### Xily XML  ###

You will find a couple of test cases in the `/test` directory. Let's just look at a quick example how to process an XML file.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<recipes>
	<recipe level="easy" cookingtime="10">
		<title>Pancakes</title>
		<ingredients>
			<item>200g flour</item>
			<item>2 eggs</item>
			<item>3 tea spoons of sugar</item>
			<item>100ml milk</item>
			<item>100ml water</item>
		</ingredients>
	</recipe>
	<recipe level="medium" cookingtime="20">
		<title>Barbecue steak</title>
		<ingredients>
			<item>250g steak</item>
			<item>5 potatoes</item>
			<item>Oil</item>
			<item>Pepper</item>
			<item>Salt</item>
			<item>Garlic</item>
			<item>Chilli</item>
		</ingredients>
	</recipe>
	<recipe level="easy" cookingtime="5">
		<title>Scrambled eggs</title>
		<ingredients>
			<item>3 eggs</item>
			<item>Pepper</item>
			<item>Salt</item>
		</ingredients>
	</recipe>
</recipes>
```

First, we will load the XML structure from a file.

```php
$xmlRecipes = Xily\XML::create('data/recipes.xml', true);
```

Xily XML uses a syntax similar to E4X to select nodes in the strucute. The `getNodeByPath` function gets all nodes that match the path's description.
This example lists all recipes which have a cookingtime under 20 minutes:

```php
$arrRecipes = $xmlRecipes->getNodesByPath('recipe(@cookingtime < 30)');
foreach ($arrRecipes as $xmlRecipe)
	echo $xmlRecipe->trace('title');
```

If you only want to select a single node, you can use the getNodeByPath function. This will select the first node that has a cooking time under 30 minutes and is easy to prepare.

```php
$xmlSomeRecipe = $xmlRecipes->getNodeByPath('recipe(@cookingtime < 30, @level == "easy")');
```

A trace returns the value or an attribute of a required node, rather than the node itself. This example get the value of the "title" node.

```php
$xmlRecipes->trace('recipe(@id == "soup").title');
```

This would be the equivalent to

```php
$xmlRecipes->getNodeByPath('recipe(@id == "soup")')->child('title')->value();
```

You can also use a sub-path as a selector. Let's get all recipes that include "Salt" as a ingredient

```php
$arrRecipes = $xmlRecipes->getNodesByPath('recipe(ingredients.item == "Salt")');
```

For more examples, check out the sample script `test/test.xml.php`


### Xily Bean ###

When developing XML-based applications, you don't want to use XML only to define
the structure and data of your application, but also to define its behaviour and presentation.
Xily Beans allow you to divide your application into single, reusable units and to access these units
by representing them in an XML structure.
This way it becomes easy for you to develop your applications directly in XML - as you would, for example, in Adobe Flex of
OpenLaszlo - as the actual coding afford is embedded inside your Xily Bean classes. This way you can focus on factors
such as design, architecture and usability rather than on the coding process itself.

#### Creating custom Beans ####

Let's create a simple example. Let's say we want to create a simple HTML form. For this example, we will be using
[Bootstrap CSS 3.0.2](http://getbootstrap.com/css/#forms)

This is how the form would look in regular HTML:


```html
<form class="form-horizontal" role="form">
  <div class="form-group">
    <label for="txtFirstname" class="col-sm-2 control-label">First name <span class="glyphicon glyphicon-asterisk"></span></label>
    <div class="col-sm-10">
      <input type="text" class="form-control" name="txtFirstname">
    </div>
  </div>
  <div class="form-group">
    <label for="txtLastname" class="col-sm-2 control-label">Last name <span class="glyphicon glyphicon-asterisk"></span></label>
    <div class="col-sm-10">
      <input type="text" class="form-control" name="txtLastname">
    </div>
  </div>
  <div class="form-group">
    <label for="txtEmail" class="col-sm-2 control-label">E-mail</label>
    <div class="col-sm-10">
      <input type="email" class="form-control" name="txtEmail">
    </div>
  </div>
  <div class="form-group">
    <label for="txtRegion" class="col-sm-2 control-label">Region</label>
    <div class="col-sm-10">
      <select class="form-control" id="txtRegion" name="region">
        <option value="">- Please Select -</option>
        <option value="North America">North America</option>
        <option value="Central America">Central America</option>
        <option value="South America">South America</option>
        <option value="European Union">European Union</option>
        <option value="Eastern Europe">Eastern Europe</option>
        <option value="Africa">Africa</option>
        <option value="Middle East">Middle East</option>
        <option value="Asia">Asia</option>
        <option value="Oceania">Oceania</option>
        <option value="The Caribbean">The Caribbean</option>
      </select>
    </div>
  </div>
</form>
```

As you can see, there's a lot of repetive HTML that could easily be avoided. Also, what if you want to
Update Bootstrap later on and class names and HTML structure might have changed? For exactly this reason,
it's more efficient to create a class for form elements


Wouldn't it be much nicer to write something like this:

```xml
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
```

Far less clutter right? In order to achieve this, we simply add two new Bean classes to our project:

```php
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
```

These two classes automatically generate the entire form. You now have reusable component classes that you can treat just as HTML tags.

In order to make it easier for you to work with different Bean libraries, Xily uses _lazy loading_ to include
the required files while parsing the XML file. You can simply include your bean directories by adding them to the
`BEAN_DIRS` array of the `Bean` class:

```php
\Xily\Bean::$BEAN_DIRS[] = LIB_DIR.'xily/src/beans';
\Xily\Bean::$BEAN_DIRS[] = LIB_DIR.'custombeans';
```

To stick with our previous example: For our custom beans `<form:frame/>` and `<form:field/>` we create a directory
in our `custombeans` directory called `form` and create two files called `frame.php` and `field.php`, each containing
the corresponding Bean class.


#### Working with Xily Data References (XDR) ####

Besides defining custom Beans, once major aspect of are Xily Data References (XDR).
XDRs enable you to dynamically reference and access data inside your document.

Let's have a simple example: You want to create a small portal page, where also want
to display the recent posts of your blog and display them nicely inside a list.

```xml
<html>
	<repeat source="#{.get(http://feeds.bbci.co.uk/news/technology/rss.xml)->xml->channel.item}">
		<div>
			<h3>#{title}</h3>
			<p>#{description}</p>
			<a href="#{link}">Read all</a>
		</div>
	</repeat>
</html>
```

XDRs offer a variety of methods to access data dynamically. There are 7 different
types of XDRs in order to provide a wide range of access possibilities:

|                XDR                 |                               Description                                |
| ---------------------------------- | ------------------------------------------------------------------------ |
| `#{.objectpath}`                   | Evaluates an XML path relative to the application's XML structure        |
| `#{datapath}`                      | Applies a path to the object's temporary dataset.                        |
| `#{objectpath->datapath}`          | Selects a local node and applys a datapath to its default dataset        |
| `#{objectpath->dataset->datapath}` | Selects a local node and applys a datapath to a specific default dataset |
| `#{object::dataset}`               | Requests the complete dataset of the specified object                    |
| `#{%global}`                       | References a global variable                                             |
| `#{%global->datapath}`             | Applies a data path to a global variable                                 |

You can also access PHP's [predefined variables](http://php.net/manual/en/reserved.variables.php):

| XDR variable name |                                                                 Description                                                                 |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| %SERVER           | Equals PHP's [$_SERVER](http://www.php.net/manual/en/reserved.variables.server.php) variable — Server and execution environment information |
| %GET              | Equals PHP's [$_GET](http://www.php.net/manual/en/reserved.variables.get.php) variable — HTTP GET variables                                 |
| %POST             | Equals PHP's [$_POST](http://www.php.net/manual/en/reserved.variables.post.php) variable — HTTP POST variables                              |
| %FILES            | Equals PHP's [$_FILES](http://www.php.net/manual/en/reserved.variables.files.php) variable — HTTP File Upload variables                     |
| %REQUEST          | Equals PHP's [$_REQUEST](http://www.php.net/manual/en/reserved.variables.request.php) variable — HTTP Request variables                     |
| %SESSION          | Equals PHP's [$_SESSION](http://www.php.net/manual/en/reserved.variables.session.php) variable — Session variables                          |
| %ENV              | Equals PHP's [$_ENV](http://www.php.net/manual/en/reserved.variables.environment.php) variable — Evariable nvironment variables             |
| %COOKIE           | Equals PHP's [$_COOKIE](http://www.php.net/manual/en/reserved.variables.cookies.php) variable  — HTTP Cookies                               |
| %HTTP             | Equals PHP's [$HTTP_RAW_POST_DATA](http://www.php.net/manual/en/reserved.variables.httprawpostdata.php) variable — Raw POST data            |
|                   |                                                                                                                                             |


### Xily Config ###

The `Config` class can be used to load and access global app configuration settings.

You can load configuration settings from either JSON or INI files, for instance:

```php
Xily\Config::load('config.ini');
```

However, you can also use the `load` method do directly load an associative array:

```php
Xily\Config::load([
	'general' => [
		'option1' => 'value1',
		'option2' => 'value2'
	]
]);
```

You can access the configuration option using the `get` method. When loading a multi-dimensional
configuration file, you can select single nodes by using a simple dot concatination, e.g.

```php
Xily\Config::get('general.option1'); // Return "value1"
```

In the same fashion you can use the `set` method to change or set single configuration values, e.g.

```php
Xily\Config::set('general.option1', 'new value');
```

The `Config` class also includes methods to initialize the return value. For instance, if your config file includes a
reference to a directory, you might want to make sure that the value has a trailing slash at the end of the directory name:

```php
Xily\Config::set('mydir', '/var/www');
Xily\Config::getDir('mydir'); // Return "/var/www/"
```

Also, you can use the `get` method to cast a specific variable type and also to initialize a default value.

```
; config.ini
[general]
dir = "/var/www"
debug = 0

[numeric]
taxrate = "1,20"
discount = "0.60"
```

Specify the expected variable type (string, int, float, array, bool, object) and a default value as additional parameters for the `get` method:

```php
Xily\Config::load('config.ini');
Xily\Config::get('general.debug', 'bool', true); // Returns FALSE
Xily\Config::get('numeric.taxrate', 'float', 1.19); // Returns the default value 1.19
Xily\Config::get('numeric.discount', 'float', 0); // Return 0.6
```

For more examples, check out the sample script `test/test.config.php`


Contribute
----------

Currently, Xily is used in a lot of different projects, so I am constantly trying to enhance and test the framework. I have started with the development of Xily in 2008, so the overall structure and functionality is pretty well tested so far. However, I would be more than happy to find more participants for the project. I am especially looking for help regarding

 * Debugging and testing
 * Adding more examples and tutorials
 * Maintenance of the Xily website
 * Writing new Xily Beans to extend the overall functionality

If you’re up to it – I am looking forward to hearing from you! Simply drop me a message on [GitHub](https://github.com/dapepe).


License
-------

Copyright (C) 2008-2016 Peter-Christoph Haider

This work is licensed under the GNU Lesser General Public License (LGPL) which should be included with this software. You may also get a copy of the GNU Lesser General Public License from [http://www.gnu.org/licenses/lgpl.txt](http://www.gnu.org/licenses/lgpl.txt).
