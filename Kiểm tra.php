<?php
// ==================== Câu 8: Giải phương trình bậc 2 ====================
function giaiPTB2($a, $b, $c) {
    if ($a == 0) {
        if ($b == 0) {
            echo ($c == 0) ? "Phương trình vô số nghiệm<br>" : "Phương trình vô nghiệm<br>";
        } else {
            echo "Phương trình có nghiệm: x = " . (-$c / $b) . "<br>";
        }
        return;
    }
    $delta = $b * $b - 4 * $a * $c;
    if ($delta < 0) {
        echo "Phương trình vô nghiệm<br>";
    } elseif ($delta == 0) {
        $x = -$b / (2 * $a);
        echo "Phương trình có nghiệm kép: x1 = x2 = $x<br>";
    } else {
        $x1 = (-$b + sqrt($delta)) / (2 * $a);
        $x2 = (-$b - sqrt($delta)) / (2 * $a);
        echo "Phương trình có 2 nghiệm phân biệt: x1 = $x1, x2 = $x2<br>";
    }
}

// ==================== Câu 9: Vẽ hình chữ nhật ====================
function veChuNhat($dai, $rong) {
    for ($i = 1; $i <= $dai; $i++) {
        for ($j = 1; $j <= $rong; $j++) {
            if ($i == 1 || $i == $dai || $j == 1 || $j == $rong) {
                echo "$ ";
            } else {
                echo "&nbsp;&nbsp; "; // giữ khoảng trắng bên trong
            }
        }
        echo "<br>";
    }
}

// ==================== Câu 10: Tính trung bình cộng ====================
function tinhTBC($arr) {
    if (count($arr) == 0) return 0;
    $tong = array_sum($arr);
    $tbc = $tong / count($arr);
    return $tbc;
}


// ==================== Chạy thử và in kết quả ====================

// Câu 8
echo "<h3>Câu 8: Giải phương trình bậc 2</h3>";
giaiPTB2(1, -3, 2); // x1=2, x2=1
echo "<br>";

// Câu 9
echo "<h3>Câu 9: Vẽ hình chữ nhật (5x12)</h3>";
veChuNhat(5, 12);
echo "<br>";

// Câu 10
echo "<h3>Câu 10: Tính trung bình cộng</h3>";
$mang = [2, 4, 6, 8, 10];
echo "Mảng: [2, 4, 6, 8, 10]<br>";
echo "Trung bình cộng = " . tinhTBC($mang) . "<br>";
?>
