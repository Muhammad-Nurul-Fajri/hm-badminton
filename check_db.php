<?php
$db = new PDO('mysql:host=localhost;dbname=hm_badminton', 'root', '');
$res = $db->query("SHOW VARIABLES LIKE 'event_scheduler'")->fetch(PDO::FETCH_ASSOC);
echo "event_scheduler: " . ($res['Value'] ?? 'unknown') . "\n";
