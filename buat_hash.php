
<?php
$password = "SuperAdmin123!";
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "<h3>Password berhasil dibuat!</h3>";
echo "<p>Password: SuperAdmin123!</p>";
echo "<p>Hash password:</p>";
echo "<textarea rows='3' cols='80'>$hash</textarea>";
?>
