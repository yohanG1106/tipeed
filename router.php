<?php
// Debug: show what files exist
echo '<pre>';
echo 'DIR: ' . __DIR__ . '<br>';
echo 'Files in DIR:<br>';
foreach(scandir(__DIR__) as $f) echo $f . '<br>';
echo 'Files in index:<br>';
if(is_dir(__DIR__ . '/index')) {
    foreach(scandir(__DIR__ . '/index') as $f) echo $f . '<br>';
}
echo '</pre>';
