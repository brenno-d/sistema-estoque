<?php
if(!isset($_SESSION)) {
    header("Location: ../auth/login.php");
    exit();
}
