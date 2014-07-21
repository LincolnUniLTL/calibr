<?php

require_once('rendering.php');

// accommodate legacy params as best I understand them (mainly to persist URLs!)
$use_legacy_params = is_numeric($_GET['prm']) && is_numeric($_GET['chm']) && !isset($_GET['month']);
if ($use_legacy_params) {
	$legacy_params = array(
		'month' => $_GET['prm'] + $_GET['chm'],
		'year' => date('Y', mktime(0, 0, 0, $_GET['prm'] + $_GET['chm'])),
	);
}

$requested_period = array(
	'month' => ( isset($_GET['month']) ? $_GET['month'] : ($use_legacy_params ? $legacy_params['month'] : $today['month']) ),
	'year' => ( isset($_GET['year']) ? $_GET['year'] : ($use_legacy_params ? $legacy_params['year'] : $today['year']) ),
	);
$requested_period['month_start'] = mktime(0, 0, 0, $requested_period['month'], 1, $requested_period['year']);
$requested_period['month_end'] = mktime(0, 0, 0, $requested_period['month'] + 1, 0, $requested_period['year']);
$requested_period['dim'] = date('t', $requested_period['month_start']); // dim == "days in month"
$requested_period['month_name'] = date('F', $requested_period['month_start']);
$requested_period['month_dow1'] = date('N', $requested_period['month_start']); // first day of month (numeric, 1-7)

$previous_period = array(
	'month' => ( $requested_period['month'] == 1 ? 12 : $requested_period['month'] - 1 ),
	'year' => ( $requested_period['month'] == 1 ? $requested_period['year'] - 1 : $requested_period['year'] ),
	);
$previous_period['month_name'] = date('F', mktime(0, 0, 0, $previous_period['month'], 1, $previous_period['year']));

$next_period = array(
	'month' => ( $requested_period['month'] == 12 ? 1 : $requested_period['month'] + 1 ),
	'year' => ( $requested_period['month'] == 12 ? $requested_period['year'] + 1 : $requested_period['year'] ),
	);
$next_period['month_name'] = date('F', mktime(0, 0, 0, $next_period['month'], 1, $next_period['year']));

$calendar = loadFromDB($requested_period['month_start'], $requested_period['month_end']);
ksort($calendar); // pretty_print_r($calendar, true);

// determine before oobs regardless of whether they will be displayed
$days_before = date('N', $requested_period['month_start']) - 1;
$before_from = add_date($requested_period['month_start'], 0 - $days_before);
$before_to = add_date($requested_period['month_start'], -1);

$calendar_before = loadFromDB($before_from, $before_to);
ksort($calendar_before);

// determine after oobs regardless of whether they will be displayed
$days_after = 7 - date('N', $requested_period['month_end']);
$after_from = add_date($requested_period['month_end'], 1);
$after_to = add_date($requested_period['month_end'], $days_after);

$calendar_after = loadFromDB($after_from, $after_to);
ksort($calendar_after);