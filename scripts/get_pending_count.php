<?php
$html = @file_get_contents('http://localhost/muhai_malangit/pending-registrations.php?ajax=1');
if ($html === false) { echo "ERROR_FETCH"; exit(1); }
if (preg_match('/badge[^>]*>([0-9]+)\s*Pending/i', $html, $m)) {
    echo $m[1];
} else {
    if (preg_match('/>([0-9]+)\s*Pending/i', $html, $m2)) echo $m2[1];
    else echo 'NOT_FOUND';
}
