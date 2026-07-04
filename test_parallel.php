<?php

$start = microtime(true);
for ($i = 0; $i < 8; $i++) {
    ob_start();
    require 'test.php';
    ob_end_clean();
    echo '.';
}
echo 'DONE ', microtime(true) - $start, PHP_EOL;

$start = microtime(true);
$runtimes = [];
for ($i = 0; $i < 8; $i++) {
    $runtimes[] = new parallel\Runtime();
}
$futures = [];
for ($i = 0; $i < 8; $i++) {
    $futures[] = $runtimes[$i]->run(function() use ($i) {
        $start = (new DateTime())->format('H:i:s.u');
        ob_start();
        require 'test.php';
        ob_end_clean();
        $end = (new DateTime())->format('H:i:s.u');
        echo '.';

        return $start . ' ' . $end . PHP_EOL;
    });
}
foreach ($futures as $future) {
    $future->value();
}
echo 'DONE ', microtime(true) - $start, PHP_EOL;
