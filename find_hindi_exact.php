<?php
$dir = 'd:/Crime Reporting System';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($it as $file) {
    if ($file->isFile() && pathinfo($file->getPathname(), PATHINFO_EXTENSION) === 'php') {
        $name = $file->getPathname();
        $c = file_get_contents($name);
        if (preg_match('/[\x{0900}-\x{097F}]/u', $c)) {
            $lines = explode("\n", $c);
            foreach($lines as $num => $line) {
                if (preg_match('/[\x{0900}-\x{097F}]/u', $line)) {
                    echo "$name:" . ($num+1) . ": $line\n";
                }
            }
        }
    }
}
?>
