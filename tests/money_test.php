<?php
// Run: php tests/money_test.php   (prints "ok" or stops at the first failing check)
// money() is used for every amount in the bank, so its Indian grouping is checked here.
if (PHP_SAPI !== 'cli') {
    exit;
}
require_once __DIR__ . '/../includes/auth.php';

$cases = [
    [0, '₹0.00'],
    [5, '₹5.00'],
    [999.5, '₹999.50'],
    [1000, '₹1,000.00'],
    [84250, '₹84,250.00'],
    [100000, '₹1,00,000.00'],
    [145000.5, '₹1,45,000.50'],
    [1250000, '₹12,50,000.00'],
    [123456789.99, '₹12,34,56,789.99'],
    [-1250.5, '₹1,250.50'],   // sign is added by the caller
];
foreach ($cases as [$in, $want]) {
    $got = money($in);
    if ($got !== $want) {
        exit("FAIL money($in): got $got, want $want\n");
    }
}
if (masked('100200300401') !== '<span aria-hidden="true">•••• </span><span class="sr-only">ending in </span>0401') {
    exit("FAIL masked()\n");
}
echo "ok\n";
