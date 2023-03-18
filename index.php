<?php
require('libraries/BRIVirtualAccount.php');

$briva = new BRIVirtualAccount(
    'UZBDMARWsHwGdccWroXZNXX3jV9MLLR0',
    'BagtSiZ4L1gLoGBi',
    'https://sandbox.partner.api.bri.co.id/'
);

$briva->institution_code = 'J104408';
$briva->briva_number = 77777;

echo '<pre>';
print_r($briva->get('0000053900'));
die;
