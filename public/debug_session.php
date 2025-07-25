<?php
session_start();

echo "<h2>Debug Session</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<h3>GET Parameters:</h3>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h3>POST Parameters:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";
?> 