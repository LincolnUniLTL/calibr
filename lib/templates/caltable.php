<div id="col-mid" class="hours">
<h2><?= $requested_period['heading'] ?></h2>
<ul class="prevnext">
	<?php
	if ($calendar['prev']) {
	?>
	<li rel="prev" class="prev"><a href="<?= $previous_period['href'] ?>" title="<?= $previous_period['title'] ?>">&#171;</a></li>
	<?php
	}
	if ($calendar['next']) {
	?>
	<li rel="next" class="next"><a href="<?= $next_period['href'] ?>" title="<?= $next_period['title'] ?>">&#187;</a></li>
	<?php
	}
	?>
</ul>

<div class="ie tblwrap">
<table class="calendar" summary="Calendar of opening hours for <?= $facility_name ?>, <?= $requested_period['heading_txt'] ?>">
<thead>
<tr>
<?php
	for ( $d=1 ; $d <=7 ; $d++) {
		$day_name = jddayofweek($d-1, 1);
		?>
		<th scope="col">
			<?= translated($day_name, "\n", array(
				'pre' => '<p class="@@LANG" lang="@@LANG">',
				'post' => '</p>',
				) )
			?>
		</th>
		<?php
	}
	?>
</tr>
</thead>

<tbody>
<tr>
<?php

$calendar_pointer = $before_from; // this is going to help us process non-existent data better

// render cells for days before 1st of month
for ($cell = 1; $cell < $requested_period['month_dow1']; $cell++) {
	$classes = array('edge', 'before');
	$calendar_pointer_Ymd = date('Y-m-d', $calendar_pointer);
	
	if (!array_key_exists($calendar_pointer_Ymd, $calendar_before)) {
		if ($show_oob_days) {
			$classes[] = 'null';
		}
		print makeEmptyCell($calendar_pointer_Ymd, $classes);
	}
	else {
		$entry = $calendar_before[$calendar_pointer_Ymd];
		print makeDateCell($entry, $classes);
	}
	$calendar_pointer = add_date($calendar_pointer, 1);
}

// render month cells
for ($cell; $cell < ($requested_period['dim'] + $requested_period['month_dow1']); $cell++) {
	if ($cell % 7 == 1) {
		?>
		</tr>

		<tr>
		<?php
	}
	$calendar_pointer_Ymd = date('Y-m-d', $calendar_pointer);
	if (!array_key_exists($calendar_pointer_Ymd, $calendar)) {
		print makeEmptyCell($calendar_pointer_Ymd, array('null'), TRUE);
	}
	else {
		$entry = $calendar[date('Y-m-d', $calendar_pointer)];
		print makeDateCell($entry);
	}
	$calendar_pointer = add_date($calendar_pointer, 1);
}

$cell--; // revert that last increment

// render any cells remaining in month
while ($cell++ % 7 > 0) {
	$classes = array('edge', 'after');
	$calendar_pointer_Ymd = date('Y-m-d', $calendar_pointer);
	
	if (!array_key_exists($calendar_pointer_Ymd, $calendar_after)) {
		if ($show_oob_days) {
			$classes[] = 'null';
		}
		print makeEmptyCell($calendar_pointer_Ymd, $classes);
	}
	else {
		$entry = $calendar_after[$calendar_pointer_Ymd];
		print makeDateCell($entry, $classes);
	}
	$calendar_pointer = add_date($calendar_pointer, 1);
}
?>

</tr>
</tbody>
</table>
</div>
