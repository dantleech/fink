<?php

$headers = getallheaders();
file_put_contents('headers', var_export($headers,true));

if ($headers['X-One'] === 'Teapot' && $headers['X-Two'] === 'Pottea') {
    http_response_code(418);
}
