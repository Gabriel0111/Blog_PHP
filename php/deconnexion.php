<?php
session_start();
session_unset();
session_destroy();

header('Location: actus.php');
//fd_exit_session(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php');
