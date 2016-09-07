<?php
//перенести все в один файл с гет параметрами ?

/**
 * Perform curl multi-request to handle all files at same time
 *
 * @param array $files file names
 * @return array response for every file
 */
function multiCurl(array $files)
{
    $fh = fopen("result.csv", "w");
    if(!$fh) {
        echo 'Set permissions so server could create file';
        die;
    }
    fclose($fh);

    //individual file curl handlers
    $chs = [];
    $responses = [];
    $mh = curl_multi_init();
    $running = null;

    foreach ($files as $file) {
        $chs[$file] = curl_init($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/parser.php');
        curl_setopt($chs[$file], CURLOPT_POST, 1);
        curl_setopt($chs[$file], CURLOPT_POSTFIELDS, ['file' => $file]);
        curl_setopt($chs[$file], CURLOPT_RETURNTRANSFER, true);
        curl_multi_add_handle($mh, $chs[$file]);
    }

    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while($running > 0);

    foreach ($chs as $file => $ch) {
        if (curl_errno($ch)) {
            $responses[$file] = ['data' => null, 'error' => curl_error($ch)];
        } else {
            $responses[$file] = ['data' => curl_multi_getcontent($ch), 'error' => null];
        }

        curl_multi_remove_handle($mh, $ch);
    }

    curl_multi_close($mh);

    return $responses;
}

$files = array_diff(scandir('files'), ['..', '.']);

$responses = multiCurl($files);

echo 'Results:<br><pre>';
var_dump($responses);
echo '</pre>';