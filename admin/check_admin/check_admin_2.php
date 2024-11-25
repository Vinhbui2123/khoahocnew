<?php
session_start();
if (isset($_SESSION['lever']) == false) {
    header('Location: ../../index.php');
}
require __DIR__ . "/check_token.php";
