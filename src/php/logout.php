<?php
session_start();
session_destroy();
setcookie("user_email", "", time() - 3600, "/"); // NOTE Cookie löschen
header("Location: login.php");
exit();
?>
