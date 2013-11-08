<?php 

require('lib/rendering.php');

$first_language = $languages[0];

$calendar = loadFromDB(mktime(), mktime());
$daystr = $today['Y-m-d'];

if ( $calendar[$daystr]->closed ) {
	$times = strip_tags(translate('Closed', $first_language));
}
else {
	$opens = timeDisplay($calendar[$daystr]->opening, $first_language);
	$closes = timeDisplay($calendar[$daystr]->closing, $first_language);
	$times = implode('&#x2013;', array($opens, $closes));
}

include 'lib/templates/day.js.php';
#print_r($calendar);
