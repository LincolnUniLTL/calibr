<?php

header("Content-type: text/javascript"); // NB this is obsoleted by IANA (http://www.iana.org/assignments/media-types/text) but required for IE support - switch to application/javascript or ecmascript some time in the future when IE has been beaten to a bloody pulp

?>
document.write("<?= $times ?>");