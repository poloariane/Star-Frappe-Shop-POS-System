<?php
// Ito ay para sa admin
echo "Admin Hash: " . password_hash('admin123', PASSWORD_DEFAULT) . "<br><br>";

// Ito ay para sa staff
echo "Staff Hash: " . password_hash('staff123', PASSWORD_DEFAULT);
?>