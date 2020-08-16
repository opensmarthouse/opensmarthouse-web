<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Zwave_database
 * @author     Chris Jackson <chris@cd-jackson.com>
 * @copyright  Copyright (C) 2015. All rights reserved.
 * @license    Eclipse Public License; see LICENSE.txt
 */
require_once('HTMLPurifier.standalone.php');

function zwaveHtmlPurifier($textHtml) {
	$config = HTMLPurifier_Config::createDefault();
	$config->set('CSS.AllowedProperties', '');
	$config->set('HTML.Allowed', 'b,br,em,h1,h2,h3,h4,h5,h6,i,p,small,strong,sub,sup,ul,ol,li,table,tr,td');
	$config->set('AutoFormat.RemoveEmpty', true);
	$config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
	$purifier = new HTMLPurifier($config);

	$tmp = $purifier->purify($textHtml);

	// Remove additional whitespace
    $tmp = preg_replace('/\s+/', ' ', $tmp);

	// Replace & to avoid XML issues!
//	$tmp = str_replace('&', '&amp;', $tmp);
	
	// And remove any whitespace off the end
	return trim($tmp);
}

?>
