<?php
session_start();
session_destroy();
header("Location: ../LANDING/index.html");
exit;