<?php

require_once('app.php');

// Let's get lots of variables available for templates ...

// $script_name = $_SERVER['SCRIPT_NAME'];

$today = array(
	'dom' => date('d'), // day of month
	'dow' => date('l'), // day of week
	'dim' => date('t'), // days in month
	'month' => date('n'),
	'year' => date('Y'),
	'Y-m-d' => date('Y-m-d'),
	);

// translations
$translations['mi'] = array(
	'January' => 'Kohi-t&#257;tea / H&#257;nuere',
	'February' =>  'Hui-ta<u>k</u>uru / P&#275;puere',
	'March' => 'Pout&#363;-te-ra<u>k</u>i / M&#257;ehe',
	'April' => 'Pae<u>k</u>a-wh&#257;wh&#257; / &#256;perira',
	'May' => 'Haratua / Mei',
	'June' => 'Pipiri / Hune',
	'July' => 'H&#333;<u>k</u>o<u>k</u>oi / H&#363;rae',
	// 'July' => 'H&#333;kokoi / H&#363;rae',
	'August' => 'Here-turi-k&#333;k&#257; / &#256;kuhata',
	'September' => 'Mahuru / Hepetema',
	'October' => 'Whiri<u>k</u>a-&#257;-nuku / Oketopa',
	'November' => 'Whiri<u>k</u>a-&#257;-ra<u>k</u>i / Noema',
	// 'November' => 'Whirika-&#257;-raki / Noema',
	'December' => 'Hakihea / T&#299;hema',
	'Monday' => 'R&#257;hina / Mane',
	'Tuesday' => 'R&#257;t&#363; / Turei',
	'Wednesday' => 'R&#257;apa / Wenerei',
	'Thursday' => 'R&#257;pare / Taite',
	'Friday' => 'R&#257;mere / Paraire',
	'Saturday' => 'R&#257;horoi',
	'Sunday' => 'R&#257;tapu',
	'Closed' => 'Kua kati',
	'Library Hours of Opening' => 'K&#257; W&#257; Puare o te Wharep&#363;r&#257;kau',
	'Opening Hours' => 'K&#257; W&#257; Puare',
	'Library, Teaching and Learning' => 'Te Wharep&#363;r&#257;kau',
	);

// ******************************
// Aux functions

// convenience function - allows us not to have to include the global $translations in lookups
function translate($text, $language, $strip = FALSE) {
	global $translations;
	// TODO: error handling??
	$trans = $translations[$language][$text];
	if ($strip) {
		$trans = strip_tags($trans);
	}
	return $trans;
}
	
function loadFromDB($from, $to) {
	global $db_settings;
	$conn = connect_mysqldb();
	$SQL = <<<EOQ
		SELECT day, UNIX_TIMESTAMP(opens), UNIX_TIMESTAMP(closes)
		FROM {$db_settings['name']}.{$db_settings['tables']['hours']}
		WHERE
			day >= FROM_UNIXTIME($from, '%Y-%m-%d')
		AND
			day <= FROM_UNIXTIME($to, '%Y-%m-%d')
		;
EOQ;

	$result = runSQL($SQL);
	
	$calendar = array();
	while ($row = mysql_fetch_array($result)) {
		$calendar[$row[0]] = new operatingDay($row[1], $row[2], $row[0]);
	}
	
	return $calendar;
}
	
function timeDisplay($time, $language='en') {
	$hour = (int) date('G', $time);
	$minute = (int) date('i', $time);
	$minuteFormat = ( $minute == 0 ? '' : ':i' ); // so we can only show minutes if they are not zero

	$format = 'G:i'; // default neutral $format in case a condition below isn't true
	switch ($language) {
		case 'mi':
			$format = "g$minuteFormat";
			switch (TRUE) {
				case ($hour > 17):
					$format .= ' (\p&#333;)';
					break;
				case ($hour > 12):
					$format .= ' (\a\h\i\a\h\i)';
					break;
				case ($hour > 0):
					$format .= ' (\a\t\a)';
					break;
			}
			break;
		default: //includes and equivalent to 'en'
			$format = "g{$minuteFormat}a";
	}
	return date($format, $time);
}

function makeDateCell($entry, $classes = array()) {
	global $today;
	$ret = '';
	
	if ($entry->closed) {
		$classes[] = 'closed'; // no need for an open class, should be implicit
	}
	if ($entry->day == $today['Y-m-d']) {
		$classes[] = 'today';
	}
	if ($entry->invalid) {
		$classes[] = 'invalid';
	}
	$class = empty($classes) ? '' : ' class="' . implode(' ', $classes) . '"';
	$ret .= "<td{$class}>\n";
	$ret .= '<h3 class="dom">' . $entry->dom() . "</h3>\n";
	$ret .= "<div class=\"times\">\n";
	if ($entry->closed) {
		$ret .= "<p>Closed</p>\n";
		$ret .= '<p class="mi" lang="mi">' . translate('Closed', 'mi') . "</p>\n";
	}
	elseif ($entry->invalid) {
		// nothing to add
	}
	else {
		$ret .= '<p>' . timeDisplay($entry->opening) . ' - ' . timeDisplay($entry->closing) . "</p>\n";
		$ret .= '<p class="mi" lang="mi">' . timeDisplay($entry->opening, 'mi') . ' - ' . timeDisplay($entry->closing, 'mi') . "</p>\n";
	}
	$ret .= "</div>\n</td>\n";
	return $ret;
}

function makeEmptyCell($day, $classes = array(), $showNumber = FALSE) {
	$class = empty($classes) ? '' : ' class="' . implode(' ', $classes) . '"';
	$contents = ( $showNumber ? '<h3 class="dom">' . dayFromYmd($day) . "</h3>\n" : '' );
	return "<td{$class}>$contents</td>\n";
}

/*
pretty_print_r($calendar);
pretty_print_r($calendar_before);
pretty_print_r($calendar_after,TRUE);
*/