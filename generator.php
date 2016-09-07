<?php

if(empty($_GET['gen'])) {
    return;
}

$types = ['a', 'b', 'c', 'd', 'e'];

for($i = 1; $i <= 50; $i++) {
    $fh = fopen("files/$i.csv", 'w');

    $lines = mt_rand(8000, 20000);
    $strings = [];
    for($j = 1; $j <= $lines; $j++) {
        $strings[] = [
            mt_rand(1, 1000),
            strtr(substr(base64_encode(openssl_random_pseudo_bytes(20)), 0, 20), '+/;', '_--'),
            $types[mt_rand(0, 4)],
            mt_rand(1000, 10000) . 'RUB'
        ];
    }

    usort($strings, function($a, $b) {
        if($a[0] == $b[0]) {
            return strcmp($a[3], $b[3]);
        }

        return $a[0] - $b[0];
    });

    foreach ($strings as $string) {
        fputcsv($fh, $string, ';');
    }

    var_dump(sizeof($strings));

    fclose($fh);
}

var_dump(memory_get_peak_usage());