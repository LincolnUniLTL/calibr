<?php
// Model database configuration file including a migration script.
// Edit this, especially the values IN CAPS, and rename it to database.php in the same directory
// See also settings.EXAMPLE.php

// Database connection
$db_settings = array(
	'host' => 'DBHOST.LIBRARY.EXAMPLE.EDU',
	'user' => 'DBUSER',
	'password' => 'S3CR3T',
	'name' => 'calendar', //no need to modify this without good reason
	'tables' => array(
		'hours' => 'libhrs', //no need to modify this without good reason
	),
);

/*
** === Migrating ===

** 	If you want to migrate data from your old calendar from Andrew Darby's Google Calendar tool (on which this tool was derived),
	use this SQL.
	
-- Remember to substitute the database(s) and table names. Do a practice run on some backup tables first.
-- It will probably throw some warnings, but that's all they are, the data seems to convert just fine.
INSERT INTO db2.table2 (id, day, opens, closes)
	SELECT libhours_id,
		DATE(ymd), 
		IF(
			is_closed=1,
			NULL,
			TIMESTAMP(
				CONCAT_WS(
					' ', 
					ymd,
					REPLACE(
						IF(
							opening RLIKE 'pm',
							ADDTIME(
								REPLACE(opening,'pm',':00'),
								'12:00:00'
							),
							opening
						),
						' ',
						''
					)
				)
			)
		),
		IF(
			is_closed=1,
			NULL,
			TIMESTAMP(
				CONCAT_WS(
					' ', 
					ymd,
					REPLACE(
						if(
							closing RLIKE 'pm',
							ADDTIME(
								REPLACE(closing,'pm',':00'),
								'12:00:00'
							),
							closing
						),
						' ',
						''
					)
				)
			)
		)
	FROM db1.table1
	WHERE libhours_id IN ( 	-- ** this satisfies the new unique day field constraint
		SELECT MAX(libhours_id) FROM db1.table1 GROUP BY ymd
	)
;

*/