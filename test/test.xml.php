<?php

header('Content-type: text/plain; charset=UTF-8');

include dirname(__FILE__).'/../src/config.php';
include dirname(__FILE__).'/../src/base.php';
include dirname(__FILE__).'/../src/xml.php';

$data = Xily\Xml::create(dirname(__FILE__).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'library.xml', 1);

/* First, we will load the XML structure from a file. */
$xmlRecipes = Xily\XML::create('data/recipes.xml', true);

/* We can also load the XML object from a string */
$xmlTest = Xily\XML::create('
	<mytag>
		<subtag attribute="hello world">some value</subtag>
		<anothertag>and so on</anothertag>
	</mytag>
');

// You can convert the document back to a string
echo $xmlRecipes->toString()."\n";

// You can also display a XML document as tree
echo $xmlRecipes->toTree()."\n";

/* Xily XML uses a syntax similar to E4X to select nodes in the strucute
 * The getNodeByPath function gets all nodes that match the path's description
 * This example lists all recipes which have a cookingtime under 30 minutes and
 * are easy to prepare: */

echo 'Recipes with cooking time < 30'."\n";
$arrRecipes = $xmlRecipes->getNodesByPath('recipe(@cookingtime < 30)');
foreach ($arrRecipes as $xmlRecipe)
	echo ' - '.$xmlRecipe->trace('title')."\n";

/* If you only want to select a single node, you can use the getNodeByPath function */
$xmlSomeRecipe = $xmlRecipes->getNodeByPath('recipe(@cookingtime < 30, @level == "easy")');
echo 'Easy recipee with cooking time lower than 30 min:'."\n"
	.$xmlSomeRecipe->toTree()."\n";

/* We can also select nodes using the children() function,
 * which simply gets all children which match the criterias
 * children($strTag, $strID, $intIndex, $strValue, $arrAttributes) */

/* Get all children */
$arrChildren = $xmlRecipes->children();

/* Get the first child */
$xmlChild = $xmlRecipes->child();

/* Get the first five children */
$arrChildren = $xmlRecipes->children('', '', '<5');

/* Get all children of type 'recipe' */
$arrChildren = $xmlRecipes->children('recipe');

/* A trace returns the value or an attribute of a required node,
 * rather than the node itself
 * Get the value of the "title" node */
echo 'Result1: '.$xmlRecipes->trace('recipe(@id == "soup").title')."\n";

/* This would be the equivalent to */
echo 'Result2: '.$xmlRecipes->getNodeByPath('recipe(@id == "soup")')->child('title')->value()."\n";

/* Get the "level"-attribute of the "recipe" node */
echo 'Result3: '.$xmlRecipes->trace('recipe(@id == "soup").@level')."\n";

/* You can also use a sub-path as a selector.
 * Let's get all recipes that include "Salt" as a ingredient */
echo 'Recipes that include "Salt" as a ingredient'."\n";
$arrRecipes = $xmlRecipes->getNodesByPath('recipe(ingredients.item == "Pepper")');
foreach ($arrRecipes as $xmlRecipe)
	echo ' - '.$xmlRecipe->trace('title')."\n";

die();


$xlyXML = xilyXML::create('data/library.xml', 1);

// echo $xlyXML -> toTree();

/* First, let's select a node */
$xmlBook = $xlyXML -> getNodeByPath('shelf.book(@isbn-10 == "0979777704")');

/* We are now going to change the value of the "title" node and the "outfit" attribute */
$xmlBook -> getNodeByPath('title') -> setValue('My Title');;
$xmlBook -> setAttribute('outfit', 'paperback');

/* You can also use the setAttribute function to add new attributes */
$xmlBook -> setAttribute('new_attribute', 'my_value');

/* Next we want to extend or manipulate the XML structure rather than only the nodes properties
First, we are going to create a new node - in our example we want to add a review for the selected book */
$xmlReview = new xilyXML('review');
$xmlReview -> setValue('The book is really good!');
$xmlReview -> setAttribute('rating', 'AAA');

/* We can now simply add this new node to the books XML-Tree by using the addChild function */
/* You can also specifiy the desired position within the tree structure, e.g. "2" for index no. 2 */
$xmlBook -> addChild($xmlReview, 2);


?>
