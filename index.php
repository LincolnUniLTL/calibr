<?php 

require('lib/render-html-period.php');

// have set page-specific variables here to keep the main template pages clean and readable as possible
$requested_period['heading'] = translated($requested_period['month_name'], ' ~ ', array( 
	'pre' => '<span lang=\"@@LANG\" class=\"@@LANG\">', 
	'post'=> '</span>',
	)
	) .  ",  {$requested_period['year']}"; 
$requested_period['heading_txt'] = strip_tags($requested_period['heading']);
$requested_period['title'] = translated('Opening Hours', ' ~ ') . " ({$requested_period['heading_txt']})";

// explicitly include the year in these prev/next links to make them work as permalinks
$previous_period['href'] = "?month={$previous_period['month']}&year={$previous_period['year']}";
$previous_period['title'] = strip_tags( translated($previous_period['month_name'], ' ~ ') );

$next_period['href'] = "?month={$next_period['month']}&year={$next_period['year']}";
$next_period['title'] = strip_tags( translated($next_period['month_name'], ' ~ ') );

include 'lib/templates/top.php';
include 'lib/templates/caltable.php';
include 'lib/templates/bottom.php';

// pretty_print_r($calendar, true);