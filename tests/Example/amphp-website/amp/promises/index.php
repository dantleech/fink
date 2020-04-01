<?php

if (substr($_SERVER['REQUEST_URI'], -1, 1) !== '/') {
    header('Location: ' . $_SERVER['REQUEST_URI'] . '/');
    exit();
}
?>

<html>
    <a href="../coroutines/">Coroutines</a>
</html>
