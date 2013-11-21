<?php

require_once('app.php');

// Let's get lots of variables available for templates ...

$monthNames_en = array_map( 'date', array_fill(1,12,'F'), array_map( 'mktime', array_fill(1,12,0), array_fill(1,12,0), array_fill(1,12,0), range(1,12)) ); #must be a neater way to do this!
$dayNames_en = array_map( 'date', array_fill(1,7,'l'), array_map( 'mktime', array_fill(1,7,0), array_fill(1,7,0), array_fill(1,7,0), array_fill(1,7,1), range(1,7)) ); # ... and this!

require_once('config/translations.php');

$translations['en'] = array_combine( $translatable_text, $translatable_text); // allows us to use English as key language and populate it from keys

// $script_name = $_SERVER['SCRIPT_NAME'];

$today = array(
	'dom' => date('d'), // day of month
	'dow' => date('l'), // day of week
	'dim' => date('t'), // days in month
	'month' => date('n'),
	'year' => date('Y'),
	'Y-m-d' => date('Y-m-d'),
	);

// ******************************
// Aux functions

// convenience function - allows us not to have to include the global $translations in lookups
// TODO: deprecate $strip param - see below
function translate($text, $language, $strip = FALSE) {
	global $translations, $languages, $translatable_text;
	
	// handle some error cases ...
	// $language not in $languages
	if (!in_array($language, $languages)) {
		cease_to_exist("Referenced language '$language' is not one of the languages listed in the translations configuration.");
	}
	// $text not in $translatable_text
	else if (!in_array($text, $translatable_text)) {
		cease_to_exist("The text \"$text\" is not one of the pieces of translatable text in the translations configuration.");
	}
	// $text not in $translations lookup for $language
	else if (!array_key_exists($text, $translations[$language])) {
		cease_to_exist("There is no translation in '$language' for \"$text\" provided in the translations configuration.");
	}
	else {
		$trans = $translations[$language][$text];
	}
	
	// deprecate this - it's rare and easy enough to just add a strip_tags where required after calling the function
	if ($strip) {
		$trans = strip_tags($trans);
	}
	
	return $trans;
}

// render text in all $languages with surrounding text as specified
// - make this into a class if any more methods are required on translation "objects"
function translated($text, $delimiter = ' ', $boundaries = array()) {
	global $languages;
	$ret = ''; // build this up to return value
	
	$ret .= ifExists($boundaries, 'beginning');

	$trans = array();
	$first_language = $languages[0];
	
	foreach ($languages as $language) {
		$trans[$language] = ifExists($boundaries, 'pre');
		$trans[$language] .= translate($text, $language);
		$trans[$language] .= ifExists($boundaries, 'post');

		//replace any @@LANG strings, which is a placeholder for language code
		$trans[$language] = str_replace('@@LANG', $language, $trans[$language]);
		
		// remove just the lang attribute for the first language - @class can stay because it may be useful to know which text was translated
		//	(plus it could have got tricky with multiple classes)
		if ($language == $first_language) {
			$trans[$language] = preg_replace('/\s+lang=["\']' . $language . '["\']/i', '', $trans[$language]);
		}
	}
	
	$ret .= implode($trans, $delimiter);
	
	$ret .= ifExists($boundaries, 'end');
	
	return $ret;
}

// return an array member if its key exists, or empty string
function ifExists($ary, $key, $default = '') {
	if ( array_key_exists($key, $ary) ) {
		return $ary[$key];
	}
	else {
		return $default;
	}
}

function loadFromDB($from, $to) {
	global $db_settings;

	// We'll sneak these into the query to determine if we are the extremities of data
	$from_previous = add_date($from, -1);
	$from_previous_Ymd = date('Y-m-d', $from_previous);
	$to_next = add_date($to, 1);
	$to_next_Ymd = date('Y-m-d', $to_next);

	$conn = connect_mysqldb();
	$SQL = <<<EOQ
		SELECT day, UNIX_TIMESTAMP(opens), UNIX_TIMESTAMP(closes)
		FROM {$db_settings['name']}.{$db_settings['tables']['hours']}
		WHERE
			day >= FROM_UNIXTIME($from_previous, '%Y-%m-%d')
		AND
			day <= FROM_UNIXTIME($to_next, '%Y-%m-%d')
		;
EOQ;

	$result = runSQL($SQL);
	
	$calendar = array();
	while ( $row = mysql_fetch_array($result) ) {
		$calendar[$row[0]] = new operatingDay($row[1], $row[2], $row[0]);
	}

	// test to see if there's data over the leading edge
	$calendar['prev'] = array_key_exists($from_previous_Ymd, $calendar);
	if ($calendar['prev']) {
		unset($calendar[$from_previous_Ymd]); // remove from $calendar, no use to us now
	}

	// see if we are at the trailing extremity
	$calendar['next'] = array_key_exists($to_next_Ymd, $calendar);
	if ($calendar['next']) {
		unset($calendar[$to_next_Ymd]); // remove from $calendar, no use to us now
	}
	
	return $calendar;
}

function timeDisplay($time, $language='en') {
	// in this function, we recognise square brackets to be discretionary display minute markers - render normally if non-zero and suppress if zero (see below)
	
	global $language_settings;
	
	$format = 'G:i'; // default neutral $format in case no condition below is true
	
	if ( array_key_exists($language, $language_settings) and array_key_exists('timeFormat', $language_settings[$language]) ) {
		if ( is_array($language_settings[$language]['timeFormat']) ) {
			$timeFormats = $language_settings[$language]['timeFormat'];
			$format = ifExists($timeFormats, 'fallback', $format); //set this if declared in case nothing gets set below

			$timesFrom = array_filter(array_keys($timeFormats), 'is_integer');
			ksort($timesFrom);

			$hour = (int) date('G', $time);
			foreach( $timesFrom as $timeFrom) {
				if ( $hour >= $timeFrom ) {
					$format = $timeFormats[$timeFrom];
				}
			}
		}
		else {
			$format = $language_settings[$language]['timeFormat'];
		}
	}

	// where we decide whether/how to show minutes to allow for suppression of zero values
	$minute = (int) date('i', $time);
	$format = preg_replace( '/\[(.+)\]/', ( $minute == 0 ? '' : '$1'), $format);
	
	return date($format, $time);
}

function makeDateCell($entry, $classes = array()) {
	global $today, $languages;
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
		$ret .= translated('Closed', "\n", array(
			'pre' => '<p lang="@@LANG" class="@@LANG">',
			'post' => '</p>',
			)
			) . "\n";
	}
	elseif ($entry->invalid) {
		// nothing to add
	}
	else {
		$first_language = $languages[0];
		foreach ($languages as $language) {
			$ret .= "<p class=\"$language\"";
			if (! $first_language == $language) {
				$ret .= " lang=\"$language\"";
			}
			$ret .= '>';
			$ret .= timeDisplay($entry->opening, $language) . ' - ' . timeDisplay($entry->closing, $language);
			$ret .= "</p>\n";
		}
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
