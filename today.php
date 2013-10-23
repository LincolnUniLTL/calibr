<?php 

require('lib/rendering.php');

$calendar = loadFromDB(mktime(), mktime());
$daystr = $today['Y-m-d'];

if ( $calendar[$daystr]->closed ) {
	$times = 'Closed';
}
else {
	$opens = date('ga', $calendar[$daystr]->opening);
	$closes = date('ga', $calendar[$daystr]->closing);
	$times = implode('&#x2013;', array($opens, $closes));
}

include 'lib/templates/day.js.php';
#print_r($calendar);
