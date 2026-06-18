<?php
/**
 * FILE: header.php
 * FUNGSI: Template header dengan cache-buster CSS
 */

require_once 'config.php';
$flash = flashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'HM Badminton Court'; ?></title>
    <!-- Cache buster: tambahkan ?v=timestamp agar CSS selalu fresh -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>style.css?v=<?php echo time(); ?>">
    <!-- Tambahan meta untuk no-cache -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body>
    <?php if ($flash): ?>
    <div class="flash-message flash-<?php echo $flash['type']; ?>">
        <?php echo $flash['message']; ?>
    </div>
    <?php endif; ?>