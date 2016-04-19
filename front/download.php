<?php
$file = 'export.csv';

if (file_exists($file)) {
    header('Cache-control: private');
    header('Content-Type: application/octet-stream');
    header('Content-Length: '.filesize($file));
    header('Content-Disposition: filename='.$file);
    readfile($file);
    exit;
}