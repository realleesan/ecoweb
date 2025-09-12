<?php
function taoArray()
{
    $array = array();
    for ($i = 1; $i <= 1000; $i++) {
        $array[] = $i;
    }
    return $array;
}
// Tìm số nguyeuen tố
function timSoNguyento($array)
{
    $numbers = array ();
    foreach ($array as $n) {
        if ($n < 2) continue;
        $LaSoNguyenTo = True;
        for ($i = 2; $i <= sqrt($n); $i++) {
            if ($n % $i ==0) {
                $LaSoNguyenTo = False;
                break;
            }
        }
        if ($LaSoNguyenTo) {
            $numbers[] = $n;
        }
    } 
    return $numbers;
}
//Gọi hàm
$Mang = taoArray();
$SoNguyenTo = timSoNguyenTo($Mang);
//Kết quả
echo "Các số nguyên tố từ 1 đến 1000 là:<br> ";
echo implode (", ", $SoNguyenTo);
?>