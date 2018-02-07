<?php

require __DIR__.'/../../config.php';

header('Content-Type: application/json');

echo json_encode(['response' => 'pong']);
