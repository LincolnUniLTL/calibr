<?php

/* TODO:
 - add semantic markup (eg RDFa) to calendar table
 - abstract out the native language and translations, make translation optional
 - update top and bottom files; genericise them for sharing
 - rendering for iCal etc

 TO FIX:
 - better separation of logic and presentation
 - stop pagination extending beyond limits of data (though it can be fun seeing Googlebot traversing back to 1970s dates)
*/

define('APP_NAME', 'CALIBR');
define('APP_VERSION', '1.0 beta1-20131011');

require('config/settings.php');

function runSQL($SQL, $dryrun = FALSE) {
	//check if $SQL is s string or array
	if (!is_array($SQL)) {
		$SQL = array($SQL);
	}
	if (!$dryrun) {
		$db = connect_mysqldb();
	}
	foreach($SQL as $statement) {
		if (VERBOSE) {
			print ( $dryrun ? '' : 'Executing ' ) . $statement . ( $dryrun ? '<br/>' : ' ' );
		}
		$result = ( $dryrun ? TRUE : mysql_query($statement) ) or die('Query failed: ' . mysql_error());
		if (VERBOSE and !$dryrun) {
			print "... Success<br/>\n";
		}
	}

	return $result;
}

function connect_mysqldb() { // let's not parameterise this, just a simple call
	global $db_settings;
	$dbconn = mysql_connect($db_settings['host'], $db_settings['user'], $db_settings['password']) or die ('Connection problem: ' . mysql_error());
	mysql_select_db($db_settings['name']) or die('Could not select database');
	return $dbconn;
}

function createOperatingDays($fields) {
	global $first_date;

	// deal with some stuff we can't handle
	if (empty($fields['Opens']) xor empty($fields['Closes'])) {
		die("Unable to process row for date {$fields['Period start']} due to one field only having a value, quitting.");
	}
	if (empty($fields['Period start'])) {
		die("Unable to process row because there is no period start value, quitting.");
	}
	
	$isClosed = empty($fields['Opens']) and empty($fields['Closes']);
	
	if (empty($fields['Period end'])) {
		// print date('Y-m-d H:i:s', $opens); exit;
		$opens = ( $isClosed ? NULL : strtotime("{$fields['Period start']} {$fields['Opens']}") );
		$closes = ( $isClosed ? NULL : strtotime("{$fields['Period end']} {$fields['Closes']}") );
		return array($fields['Period start'] => new operatingDay($opens, $closes));
	}
	else {
		$current_start = strtotime("{$fields['Period start']} 00:00:00");
		$recur_to_timestamp = strtotime("{$fields['Period end']} 23:59:59"); //TODO: check this
		$recurring_days = empty($fields['Recurring days']) ? range(1,7) : str_split($fields['Recurring days']);

		while($current_start <= $recur_to_timestamp) {
			$current_dow = date('N', $current_start);
			if ( $current_start >= $first_date and in_array($current_dow, $recurring_days) ) {
				$opens = ( $isClosed ? NULL : strtotime(date('Y-m-d', $current_start) . " {$fields['Opens']}") );
				$closes = ( $isClosed ? NULL : strtotime(date('Y-m-d', $current_start) . " {$fields['Closes']}") );
				$values[date('Y-m-d', $current_start)] = new operatingDay($opens, $closes);
			}
			$current_start = add_date($current_start, 1);
		}
		return $values;
	}
	
}

// in lieu of DateTime::add being available (PHP 5.3)
function add_date($stamp, $days=0, $months=0, $years=0) {
	return mktime(
		date('H', $stamp),
		date('i', $stamp), 
		date('s', $stamp), 
		date('m', $stamp) + $months,
		date('d', $stamp) + $days,
		date('Y', $stamp) + $years );
}

function pretty_print_r($ary, $terminate=FALSE, $caption=NULL) {
	if (DEBUG) {
		if (!is_null($caption)) {
			print "<h2 class=\"debug\">$caption</h2>\n";
		}
		print "<pre>\n";
		print_r($ary);
		print "\n</pre>\n";
	}
	if ($terminate) {
		exit;
	}
}

class operatingDay {
	public $day;
	public $opening, $closing;
	public $closed;
	public $invalid;
	/* public $oob; */
	
	public function __construct($opens, $closes, $day = NULL) {
		$this->closed = ( is_null($opens) and is_null($closes) );
		$this->invalid = ( is_null($opens) xor is_null($closes) );
		$this->invalid = $this->invalid or ( !$this->closed and date('Y-m-d', $this->opening) != date('Y-m-d', $this->closing) );
		$this->opening = $opens;
		$this->closing = $closes;
		/* $this->oob = FALSE; // default ... ? */
		if ( is_null($day)) {
			if ( !($this->closed or $this->invalid) ) {
				$this->day = date('Y-m-d', $this->opening);
			} // otherwise we need to pick it up on save to DB
		}
		else {
			$this->day = $day;
		}
	}
	
	public function save($day = NULL, $dryrun = FALSE) { // $dryrun will result in the SQL query being returned, which is handy for saving them up into an array to run in a batch transaction
		global $db_settings;
		if ($this->closed) {
			if (!isset($this->day) and is_null($day)) {
				// we can't do that
				return FALSE;
			}
			elseif (!isset($this->day)) {
				$this->day = $day;
			}
		}
		$SQLfieldnames = 'day' . ($this->closed ? '' : ', opens, closes');
		$SQLfieldvalues = "\"$this->day\"" . ($this->closed ? '' : ", FROM_UNIXTIME({$this->opening}), FROM_UNIXTIME({$this->closing})");
		$SQL = <<<EOQ
INSERT INTO {$db_settings['name']}.{$db_settings['tables']['hours']}
	($SQLfieldnames)
	VALUES ($SQLfieldvalues);
EOQ;
		$result = ( $dryrun ? $SQL : runSQL($SQL) );
		// echo "$SQL<br/>\n";
		return $result;
	}
	
	public function dom() { // return day of month or NULL
		return isset($this->day) ? dayFromYmd($this->day) : NULL;
	}

}

function dayFromYmd($Ymd) {
	return (int) substr($Ymd, 8);
}

// wrapper for die() to force a 500 error
function cease_to_exist($message) {
	#$buf = ob_get_clean();
	header('HTTP/1.0 500 Internal Server Error');
	#echo $buf;
	die($message);
}