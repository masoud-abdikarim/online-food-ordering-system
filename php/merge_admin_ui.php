<?php
/**
 * One-time merge: splices admin_new.html into admin_dashboard.php after PHP block.
 * Run: php merge_admin_ui.php
 */
$dir = __DIR__;
$srcPath = $dir . '/admin_dashboard.php';
$htmlPath = $dir . '/admin_new.html';

$src = file_get_contents($srcPath);
$html = file_get_contents($htmlPath);

if ($html === false || $src === false) {
    fwrite(STDERR, "Missing file.\n");
    exit(1);
}

$p = strpos($src, '<!DOCTYPE html>');
if ($p === false) {
    fwrite(STDERR, "Could not find <!DOCTYPE html> in admin_dashboard.php\n");
    exit(1);
}

$prefix = substr($src, 0, $p);
file_put_contents($srcPath, $prefix . $html);
echo "Merged OK\n";
