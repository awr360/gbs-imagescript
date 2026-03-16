<?php
// Base directory to scan for images
$baseDir = './images'; // Change this to the path of your images folder

// Base URL for the images
$baseUrl = 'http://images.gbs.adventistinbox.org/';

// Function to recursively get images organized by folder
function getImages($dir) {
    $images = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $path = $file->getPath();
            $relativePath = substr($path, strlen($dir) + 1) . '/' . $file->getFilename();
            $folder = dirname($relativePath);
            if ($folder == '.') $folder = '';
            $images[$folder][] = $relativePath;
        }
    }
    return $images;
}

// Get the images
$images = getImages($baseDir);

// Output HTML
echo '<html><head><title>Image List</title><style>ul { list-style: none; display: flex; flex-wrap: wrap; } li { margin: 10px; text-align: center; }</style></head><body>';
echo '<h1>Image Gallery</h1>';

foreach ($images as $folder => $files) {
    if ($folder == '') {
        echo '<h2>Root</h2><ul>';
        foreach ($files as $file) {
            $url = rtrim($baseUrl, '/') . '/' . ltrim($file, '/');
            echo "<li><img src='$url' style='max-width:120px; max-height:120px;' alt='$file'><br><a href='$url'>$file</a></li>";
        }
        echo '</ul>';
    } else {
        echo "<details><summary>$folder</summary><ul>";
        foreach ($files as $file) {
            $url = rtrim($baseUrl, '/') . '/' . ltrim($file, '/');
            echo "<li><img src='$url' style='max-width:120px; max-height:120px;' alt='$file'><br><a href='$url'>$file</a></li>";
        }
        echo '</ul></details>';
    }
}

echo '</body></html>';
?>