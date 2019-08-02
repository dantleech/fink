<?php

$headers = getallheaders();

if ($headers['x-one'] === 'Teapot' && $headers['x-two'] === 'Pottea') {
    http_response_code(418);
}
