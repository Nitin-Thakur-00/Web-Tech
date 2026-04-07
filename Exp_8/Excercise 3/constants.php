<?php
define('SITE_NAME', 'Chronos');
define('VERSION', '1.0.0');
define('MAX_ITEMS_PER_PAGE', 26);

echo "<h1>Constants</h1>";
echo "<p>Site Name: " . SITE_NAME . "</p>";
echo "<p>Version: " . VERSION . "</p>";
echo "<p>Max Items Per Page: " . MAX_ITEMS_PER_PAGE . "</p>";

echo "<hr>";

echo "<h2>Strings: Single vs Double Quotes</h2>";
$language = 'PHP';

echo "<p>Single quotes: " . '<br>' . '$language is great' . "</p>";
echo "<p>Double quotes: " . '<br>' . "$language is great" . "</p>";
?>
