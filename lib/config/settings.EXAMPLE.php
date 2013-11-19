<?php
// Model configuration/settings file.
// Edit the values here and then rename the file to settings.php in the same directory
// See also database.EXAMPLE.php

require('database.php'); // probably leave this alone

define('DEBUG', FALSE);
define('VERBOSE', FALSE);

$populate_months = 6; // number of months to load into the database - possibly legacy and very unlikely to have an effect if set at 6 months

$show_oob_days = TRUE; // whether to show out-of-band days (days in spare cells for prev/next month) in monthly calendar displays

$data_file = 'calendar.csv';

$facility_name = 'Example University Grand Opulent Central Library';

$branded = TRUE; // whether to show (configurable) "powered by" style text linking to the software at the calendar bottom

$timezone = 'Pacific/Auckland'; // server time - used in loading data only, may be legacy(?), and if you trust your PHP config you can probably load that environment variable here

?>