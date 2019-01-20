<?php

if (!isset($_COOKIE['hello'])) {
    http_response_code(403);
}
