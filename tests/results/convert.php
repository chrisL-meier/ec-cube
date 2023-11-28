<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$existence_check = [];
$fp = fopen(__DIR__.'/error_csv.csv', 'w');

$amount = [
    0 => 0,
    1 => 0,
    2 => 0,
    3 => 0,
    4 => 0,
    5 => 0,
    6 => 0,
    7 => 0,
    8 => 0,
    9 => 0,
];
for ($i = 0; $i <= 9; $i++) {
    $str = file_get_contents(__DIR__.'/level_'.$i.'.json');
    $json = json_decode($str, true);
    $files = $json['files'];
    foreach ($files as $error_file => $error_array) {
        foreach ($error_array['messages'] as $message) {
            $validator = true;
            $line_data = [];
            $line_data['file_name'] = $error_file;
            $line_data['level'] = $i;
            $line_data['line'] = $message['line'];
            $line_data['message'] = $message['message'];
            foreach ($existence_check as $check) {
                if ($line_data['file_name'] == $check['file_name'] &&
                    $line_data['line'] == $check['line'] &&
                    $line_data['message'] == $check['message'] &&
                    $message['ignorable'] == $check['ignorable']
                ) {
                    $validator = false;
                }
            }
            if ($validator) {
                $amount[$i]++;
                fputcsv($fp, $line_data);
                $line_data['ignorable'] = $message['ignorable'];
                $existence_check[] = $line_data;
            }
        }
    }
}
fclose($fp);
// 結果件数をJSONに書き込み
$fp = fopen(__DIR__.'/amount.json', 'w');
$json = [];
foreach ($amount as $key => $value) {
    $json["Level {$key}"] = $value;
}
fwrite($fp, json_encode($json));
fclose($fp);
