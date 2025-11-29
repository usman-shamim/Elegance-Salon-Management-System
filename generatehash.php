<?php
$password = 'admin123';

// Use the recommended, secure, default algorithm
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Plaintext Password: " . $password . "\n";
echo "Generated Hash:     " . $hash . "\n";
?>