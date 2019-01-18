<?php
/**
 * @copyright 	&copy; 2005-2019 PHPBoost https://www.phpboost.com
 * @license 	https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL-3.0
 * @author      Julien BRISWALTER [j1.seth@phpboost.com]
 * @version   	PHPBoost 5.2 - last update: 2018 05 25
 * @since   	PHPBoost 5.1 - 2018 05 25
 * @contributor Sebastien LARTIGUE [babsolune@phpboost.com]
*/

####################################################
#                     English                      #
####################################################

use \Wiki\util\ModUrlBuilder;

$lang['root.category.description'] = 'Welcome to the wiki section of the website!
<br /><br />
One category and one article were created to show you how this module works. Here are some tips to get started on this module.
<br /><br />
<ul class="formatter-ul">
	<li class="formatter-li"> To configure or customize the module homepage, go into the <a href="' . ModUrlBuilder::configuration()->relative() . '">module administration</a></li>
	<li class="formatter-li"> To create categories, <a href="' . ModUrlBuilder::add_category()->relative() . '">clic here</a></li>
	<li class="formatter-li"> To create articles, <a href="' . ModUrlBuilder::add_item()->relative() . '">clic here</a></li>
</ul>
<br />To learn more, don \'t hesitate to consult the documentation for the module on <a href="https://www.phpboost.com">PHPBoost</a> website.';
?>
