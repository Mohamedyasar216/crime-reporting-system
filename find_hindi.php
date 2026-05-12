<?php
$dir = 'd:/Crime Reporting System';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($it as $file) {
    if ($file->isFile()) {
        $name = $file->getPathname();
        if (pathinfo($name, PATHINFO_EXTENSION) !== 'php') continue;
        $c = file_get_contents($name);
        if (preg_match('/[^\x00-\x7F]/', $c)) {
            $lines = explode("\n", $c);
            foreach($lines as $num => $line) {
                if (preg_match('/[^\x00-\x7F]/', $line)) {
                    echo "$name:" . ($num+1) . ": $line\n";
                }
            }
        }
    }
}
