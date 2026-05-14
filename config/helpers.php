<?php
function require_role($role){ session_start(); if(!isset($_SESSION['role']) || $_SESSION['role'] !== $role){ header('Location: ../auth/login.php'); exit(); }}
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
