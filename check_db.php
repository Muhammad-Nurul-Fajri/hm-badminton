<?php
require_once __DIR__ . '/config/config.php';
$db = getDB();
try {
    $db->exec("DROP TRIGGER IF EXISTS trg_test");
    $db->exec("CREATE TRIGGER trg_test BEFORE UPDATE ON users FOR EACH ROW BEGIN END");
    $db->exec("DROP TRIGGER IF EXISTS trg_test");
    echo "Triggers supported and working!\n";
} catch (Exception $e) {
    echo "Trigger error: " . $e->getMessage() . "\n";
}
