<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: /estudos/sistema-estoque/auth/login.php");
    exit();
}
