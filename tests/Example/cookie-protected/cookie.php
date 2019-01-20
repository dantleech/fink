<?php

if (!isset($_COOKIES['hello'])) {
    http_response_code(404);
}
setcookie('hello', 'world');
