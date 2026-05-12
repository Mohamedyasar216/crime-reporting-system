<?php
$f = file('public/police_crimes.php');
for($i=235; $i<=238; $i++) {
    echo "Line " . ($i+1) . ": " . $f[$i];
    echo "Hex: " . bin2hex($f[$i]) . "\n";
}
?>
