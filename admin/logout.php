<?php
// logout.php - Sessiyani tugatish
session_start();
session_destroy();
header('Location: login.php');
exit;
