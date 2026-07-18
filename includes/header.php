<?php
/**
 * FILE: includes/header.php
 * FUNGSI: Template header HTML dengan CSS & flash messages
 */

$flash = flashMessage();

// Cache-busting berdasarkan file modification time
$css_file = BASE_PATH . 'assets/css/style.css';
$css_ver = file_exists($css_file) ? filemtime($css_file) : time();
$ds_file = BASE_PATH . 'assets/css/design-system.css';
$ds_ver = file_exists($ds_file) ? filemtime($ds_file) : time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'HM Badminton Court'); ?></title>
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/design-system.css?v=<?php echo $ds_ver; ?>">
    
    <!-- Unified Stylesheet -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo $css_ver; ?>">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body>
    <?php if ($flash): ?>
    <div class="flash-message flash-<?php echo htmlspecialchars($flash['type']); ?>">
        <span><?php echo htmlspecialchars($flash['message']); ?></span>
        <button type="button" class="flash-close-btn" onclick="this.parentElement.remove()" style="background:none; border:none; color:white; font-size:1.2rem; cursor:pointer; font-weight:bold; margin-left:12px; line-height:1;">&times;</button>
    </div>
    <?php endif; ?>
