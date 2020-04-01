<?php

$headers = getallheaders();

if ($headers['X-One'] === 'Teapot' && $headers['X-Two'] === 'Pottea') {
    http_response_code(418);
}
