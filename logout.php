<?php
session_start();
session_destroy();
header("Location: /portal-repo-og/login.php");
exit;
?>