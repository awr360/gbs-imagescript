<?php
// Array of three-letter language codes to hide as separate folders
$languageCodes = [
    'eng', 'spa', 'deu', 'fra', 'ita', 'por', 'rus', 'chi', 'jpn', 'kor',
    'ara', 'hin', 'ben', 'urd', 'ind', 'tur', 'per', 'tha', 'vie', 'msa',
    'tam', 'tel', 'mar', 'guj', 'kan', 'mal', 'ori', 'pan', 'asm', 'mai',
    'nep', 'sin', 'bur', 'khm', 'lao', 'mon', 'tib', 'uig', 'kaz', 'kir',
    'tjk', 'tkm', 'uzb', 'aze', 'geo', 'arm', 'bel', 'ukr', 'bul', 'mac',
    'slo', 'cze', 'pol', 'hun', 'alb', 'bos', 'hrv', 'srp', 'slv', 'rom',
    'mol', 'gre', 'heb', 'yid', 'ara', 'fas', 'kur', 'pus', 'snd', 'bal',
    'bra', 'kok', 'mni', 'san', 'bho', 'awa', 'bjj', 'mag', 'mai', 'bho',
    'new', 'bih', 'bho', 'dhi', 'dot', 'kha', 'khn', 'khr', 'kjp', 'kdt',
    'khm', 'kha', 'kjg', 'krr', 'kdt', 'khm', 'krr', 'kxv', 'kha', 'kjp',
    'kdt', 'khm', 'krr', 'kxv', 'kha', 'kjp', 'kdt', 'khm', 'krr', 'kxv'
];

// Base directory to scan for images
$baseDir = './images'; // Change this to the path of your images folder

// Base URL for the images - use environment variable or default to relative path
$baseUrl = getenv('APP_BASE_URL') ?: '/images/';
// $baseUrl = 'https://connect.awr.org/imgmg/images/';

// Load and parse manifest
$manifestData = [];
$manifestFile = rtrim($baseDir, '/') . '/manifest.json';
if (file_exists($manifestFile)) {
    $manifestData = json_decode(file_get_contents($manifestFile), true) ?: [];
}

// Helper to filter valid image extensions
function isImage($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}

// Function to get top-level folders
function getTopLevelFolders($dir) {
    global $manifestData;
    $folders = [];
    foreach ($manifestData as $path) {
        $parts = explode('/', ltrim($path, '/'));
        if (count($parts) > 1) {
            $folders[$parts[0]] = true;
        } else if (count($parts) == 1 && !isImage($parts[0])) {
            $folders[$parts[0]] = true;
        }
    }
    return array_keys($folders);
}

// Function to get subfolders for a specific top-level folder
function getSubfoldersForFolder($baseDir, $folder) {
    global $manifestData, $languageCodes;
    $subfolders = [];
    foreach ($manifestData as $path) {
        $parts = explode('/', ltrim($path, '/'));
        if (count($parts) > 2 && $parts[0] === $folder) {
            $subfolderName = $parts[1];
            if (!in_array(strtolower($subfolderName), $languageCodes) && !isImage($subfolderName)) {
                $subfolders[$subfolderName] = true;
            }
        }
    }
    return array_keys($subfolders);
}

// Function to get images for a specific folder path
function getImagesInFolder($folderPath, $baseUrl) {
    global $manifestData, $baseDir;
    $images = [];
    $targetPrefix = ltrim(substr($folderPath, strlen($baseDir)), '/');
    if ($targetPrefix !== '') $targetPrefix .= '/';

    foreach ($manifestData as $path) {
        if ($targetPrefix === '' || strpos($path, $targetPrefix) === 0) {
            $remainder = substr($path, strlen($targetPrefix));
            if (strpos($remainder, '/') === false && isImage($remainder)) {
                $images[] = $remainder;
            }
        }
    }
    return $images;
}

// Function to get language-specific images for any folder path
function getLangSpecificImages($basePath, $folderRelativePath) {
    global $manifestData, $languageCodes;
    $langImages = [];
    $targetPrefix = ltrim($folderRelativePath, '/') . '/';
    
    foreach ($manifestData as $path) {
        if (strpos($path, $targetPrefix) === 0) {
            $remainder = substr($path, strlen($targetPrefix));
            $parts = explode('/', $remainder);
            if (count($parts) == 2) {
                $lang = $parts[0];
                $filename = $parts[1];
                if (in_array(strtolower($lang), $languageCodes) && isImage($filename)) {
                    $langImages[$lang][] = rtrim($folderRelativePath, '/') . '/' . $lang . '/' . $filename;
                }
            }
        }
    }
    return $langImages;
}

// Get the top-level folders
$topLevelFolders = getTopLevelFolders($baseDir);

// Output HTML
?>
<html>
<head>
    <title>Image List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        h1 {
            color: #333;
        }
        .image-section {
            background-color: white;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .section-heading {
            background-color: #007bff;
            color: white;
            padding: 15px;
            cursor: pointer;
            user-select: none;
            font-size: 18px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-heading:hover {
            background-color: #0056b3;
        }
        .toggle-icon {
            font-size: 24px;
        }
        .section-heading.collapsed + .image-gallery {
            display: none;
        }
        .image-gallery {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            padding: 20px;
            margin: 0;
            gap: 15px;
        }
        .image-item {
            text-align: center;
            flex: 0 0 auto;
        }
        .image-item img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 4px;
            border: 1px solid #ddd;
            cursor: pointer;
        }
        .image-item a {
            display: block;
            margin-top: 8px;
            font-size: 12px;
            color: #007bff;
            text-decoration: none;
            word-break: break-all;
        }
        .image-item a:hover {
            text-decoration: underline;
        }
        .lang-note {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        .subfolder-section {
            margin: 15px 0;
            border-left: 3px solid #007bff;
            padding-left: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            width: 100%;
        }
        .subfolder-heading {
            background-color: #e9ecef;
            color: #495057;
            padding: 10px 15px;
            cursor: pointer;
            user-select: none;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .subfolder-heading:hover {
            background-color: #dee2e6;
        }
        .subfolder-heading.collapsed + .subfolder-gallery {
            display: none;
        }
        .subfolder-gallery {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            padding: 0;
            margin: 0;
            gap: 15px;
        }
        .toggle-icon::before {
            content: "▼";
        }
        .section-heading.collapsed .toggle-icon::before,
        .subfolder-heading.collapsed .toggle-icon::before {
            content: "▶";
        }
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .lightbox img {
            max-width: 90%;
            max-height: 90%;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Global Bible School Image Gallery</h1>

    <?php if (empty($topLevelFolders)): ?>
        <p style="text-align: center; color: #666; margin-top: 50px;">
            No folders found in <code><?php echo htmlspecialchars($baseDir); ?></code>.<br>
            Please check if the sync script is running and has permissions.
        </p>
    <?php endif; ?>

    <?php foreach ($topLevelFolders as $folder): ?>
        <?php
        $subfolders = getSubfoldersForFolder($baseDir, $folder);
        $folderPath = $baseDir . '/' . $folder;
        $folderImages = getImagesInFolder($folderPath, $baseUrl);
        $folderLangImages = getLangSpecificImages($baseDir, $folder);
        ?>

        <div class="image-section">
            <div class="section-heading" onclick="toggleSection(this)">
                <span><?php echo htmlspecialchars($folder); ?></span>
                <span class="toggle-icon"></span>
            </div>
            <ul class="image-gallery">
                <?php if (empty($folderImages) && empty($folderLangImages) && empty($subfolders)): ?>
                    <li style="color: #999; padding: 10px;">No images or subfolders found in this section.</li>
                <?php endif; ?>
                <!-- Images directly in the main folder -->
                <?php foreach ($folderImages as $image): ?>
                    <?php
                        $url = rtrim($baseUrl, '/') . '/' . $folder . '/' . $image;
                    ?>
                    <li class="image-item">
                        <img src="<?php echo htmlspecialchars($url); ?>" alt="<?php echo htmlspecialchars($image); ?>" data-full-url="<?php echo htmlspecialchars($url); ?>">
                        <a href="<?php echo htmlspecialchars($url); ?>" title="<?php echo htmlspecialchars($image); ?>">
                            <?php echo htmlspecialchars($image); ?>
                        </a>
                    </li>
                <?php endforeach; ?>

                <!-- Language variants directly in the main folder -->
                <?php if (!empty($folderLangImages)): ?>
                    <?php foreach ($folderLangImages as $lang => $langFiles): ?>
                        <?php foreach ($langFiles as $file): ?>
                            <?php
                                $templateUrl = rtrim($baseUrl, '/') . '/' . str_replace($lang, '_lang_', $file);
                                $previewUrl = rtrim($baseUrl, '/') . '/' . $file;
                            ?>
                            <li class="image-item">
                                <img src="<?php echo htmlspecialchars($previewUrl); ?>" alt="<?php echo htmlspecialchars(basename($file)); ?>" data-full-url="<?php echo htmlspecialchars($templateUrl); ?>">
                                <a href="<?php echo htmlspecialchars($templateUrl); ?>" title="<?php echo htmlspecialchars(basename($file)); ?>">
                                    <?php echo htmlspecialchars(basename($file)); ?> (<?php echo htmlspecialchars($lang); ?>)
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Subfolders -->
                <?php foreach ($subfolders as $subfolder): ?>
                    <?php
                        $subfolderRelativePath = $folder . '/' . $subfolder;
                        $subfolderLangImages = getLangSpecificImages($baseDir, $subfolderRelativePath);
                        $hasSubfolderLang = !empty($subfolderLangImages);
                    ?>
                    <li class="subfolder-section">
                        <div class="subfolder-heading collapsed" onclick="toggleSection(this)">
                            <span><?php echo htmlspecialchars($subfolder); ?></span>
                            <span class="toggle-icon"></span>
                        </div>
                        <ul class="subfolder-gallery">
                            <?php
                                $subfolderPath = $folderPath . '/' . $subfolder;
                                $subfolderImages = getImagesInFolder($subfolderPath, $baseUrl);
                                foreach ($subfolderImages as $image):
                                    $url = rtrim($baseUrl, '/') . '/' . $folder . '/' . $subfolder . '/' . $image;
                            ?>
                                <li class="image-item">
                                    <img src="<?php echo htmlspecialchars($url); ?>" alt="<?php echo htmlspecialchars($image); ?>" data-full-url="<?php echo htmlspecialchars($url); ?>">
                                    <a href="<?php echo htmlspecialchars($url); ?>" title="<?php echo htmlspecialchars($image); ?>">
                                        <?php echo htmlspecialchars($image); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>

                            <!-- Language variants for this subfolder -->
                            <?php if ($hasSubfolderLang): ?>
                                <?php foreach ($subfolderLangImages as $lang => $langFiles): ?>
                                    <?php foreach ($langFiles as $file): ?>
                                        <?php
                                            $templateUrl = rtrim($baseUrl, '/') . '/' . str_replace($lang, '_lang_', $file);
                                            $previewUrl = rtrim($baseUrl, '/') . '/' . $file;
                                        ?>
                                        <li class="image-item">
                                            <img src="<?php echo htmlspecialchars($previewUrl); ?>" alt="<?php echo htmlspecialchars(basename($file)); ?>" data-full-url="<?php echo htmlspecialchars($templateUrl); ?>">
                                            <a href="<?php echo htmlspecialchars($templateUrl); ?>" title="<?php echo htmlspecialchars(basename($file)); ?>">
                                                <?php echo htmlspecialchars(basename($file)); ?> (<?php echo htmlspecialchars($lang); ?>)
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>

    <script>
        function toggleSection(heading) {
            heading.classList.toggle('collapsed');
        }
        document.addEventListener('DOMContentLoaded', function() {
            const lightbox = document.getElementById('lightbox');
            const lightboxImg = document.getElementById('lightbox-img');
            let currentUrl = '';
            document.querySelectorAll('.image-item img').forEach(img => {
                img.addEventListener('click', function() {
                    currentUrl = this.dataset.fullUrl;
                    lightboxImg.src = this.src;
                    lightbox.style.display = 'flex';
                });
            });
            lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox) {
                    lightbox.style.display = 'none';
                }
            });
            lightboxImg.addEventListener('click', function() {
                lightbox.style.display = 'none';
                navigator.clipboard.writeText(currentUrl);
            });
            document.querySelectorAll('.image-item a').forEach(a => {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    navigator.clipboard.writeText(this.href);
                });
            });
        });
    </script>
    <div id="lightbox" class="lightbox"><img id="lightbox-img" src="" alt=""></div>
</body>
</html>
<?php
?>