<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: templates/dashboard");
    exit();
} else {
    header("Location: templates/login");
    exit();
}
?>