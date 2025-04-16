<?php
require_once 'core/config.php';

session_start();
session_unset();
session_destroy();

header("Location: login.php");
exit;