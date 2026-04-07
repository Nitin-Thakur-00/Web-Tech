<?php
$str = "Hello, World!";
$int = 26;
$float = 3.14159;
$bool = true;
$var_null = null;
$arr = [1, 2, 3];

echo "<h1>Data Types</h1>";

echo "<h2>String</h2>";
echo "<p>Value: $str</p>";
echo "<p>Type: " . gettype($str) . "</p>";
echo "<p>is_string: " . (is_string($str) ? 'true' : 'false') . "</p>";

echo "<h2>Integer</h2>";
echo "<p>Value: $int</p>";
echo "<p>Type: " . gettype($int) . "</p>";
echo "<p>is_int: " . (is_int($int) ? 'true' : 'false') . "</p>";

echo "<h2>Float</h2>";
echo "<p>Value: $float</p>";
echo "<p>Type: " . gettype($float) . "</p>";
echo "<p>is_float: " . (is_float($float) ? 'true' : 'false') . "</p>";

echo "<h2>Boolean</h2>";
echo "<p>Value: " . ($bool ? 'true' : 'false') . "</p>";
echo "<p>Type: " . gettype($bool) . "</p>";
echo "<p>is_bool: " . (is_bool($bool) ? 'true' : 'false') . "</p>";

echo "<h2>Null</h2>";
echo "<p>Value: " . ($var_null === null ? 'null' : 'not null') . "</p>";
echo "<p>Type: " . gettype($var_null) . "</p>";
echo "<p>is_null: " . (is_null($var_null) ? 'true' : 'false') . "</p>";

echo "<h2>Array</h2>";
echo "<p>Value: [1, 2, 3]</p>";
echo "<p>Type: " . gettype($arr) . "</p>";
echo "<p>is_array: " . (is_array($arr) ? 'true' : 'false') . "</p>";
?>
