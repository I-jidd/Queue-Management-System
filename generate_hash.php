<?php
// Quick script to generate password hash
$password = 'admin123';
echo password_hash($password, PASSWORD_DEFAULT);
?>

