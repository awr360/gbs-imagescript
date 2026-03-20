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

// Base URL for the images
$baseUrl = 'https://connect.awr.org/imgmg/images/';
// $baseUrl = 'http://images.gbs.adventistinbox.org/';

// Function to get top-level folders
function getTopLevelFolders($dir) {
    $folders = [];
    if (!is_dir($dir)) {
        return $folders;
    }
    
    $iterator = new DirectoryIterator($dir);
    foreach ($iterator as $item) {
        if ($item->isDir() && !$item->isDot()) {
            $folders[] = $item->getFilename();
        }
    }
    return $folders;
}

// Function to get subfolders for a specific top-level folder
function getSubfoldersForFolder($baseDir, $folder) {
    global $languageCodes;
    $subfolders = [];
    $folderPath = $baseDir . '/' . $folder;
    
    if (!is_dir($folderPath)) {
        return $subfolders;
    }
    
    $iterator = new DirectoryIterator($folderPath);
    foreach ($iterator as $item) {
        if ($item->isDir() && !$item->isDot()) {
            $subfolderName = $item->getFilename();
            // Skip language folders
            if (!in_array(strtolower($subfolderName), $languageCodes)) {
                $subfolders[] = $subfolderName;
            }
        }
    }
    
    return $subfolders;
}

// Function to get images for a specific folder path
function getImagesInFolder($folderPath, $baseUrl) {
    $images = [];
    
    if (!is_dir($folderPath)) {
        return $images;
    }
    
    $iterator = new DirectoryIterator($folderPath);
    foreach ($iterator as $item) {
        if ($item->isFile() && in_array(strtolower($item->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $images[] = $item->getFilename();
        }
    }
    
    return $images;
}

// Function to get language-specific images for a specific subfolder
function getLangSpecificImagesForSubfolder($baseDir, $folder, $subfolder) {
    global $languageCodes;
    $langImages = [];
    $subfolderPath = $baseDir . '/' . $folder . '/' . $subfolder;

    if (!is_dir($subfolderPath)) {
        return $langImages;
    }

    foreach ($languageCodes as $lang) {
        $langPath = $subfolderPath . '/' . $lang;
        if (is_dir($langPath)) {
            $iterator = new DirectoryIterator($langPath);
            foreach ($iterator as $file) {
                if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $langImages[$lang][] = $folder . '/' . $subfolder . '/' . $lang . '/' . $file->getFilename();
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

    <?php foreach ($topLevelFolders as $folder): ?>
        <?php
        $subfolders = getSubfoldersForFolder($baseDir, $folder);
        $folderPath = $baseDir . '/' . $folder;
        $folderImages = getImagesInFolder($folderPath, $baseUrl);
        ?>

        <div class="image-section">
            <div class="section-heading collapsed" onclick="toggleSection(this)">
                <span><?php echo htmlspecialchars($folder); ?></span>
                <span class="toggle-icon"></span>
            </div>
            <ul class="image-gallery">
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

                <!-- Subfolders -->
                <?php foreach ($subfolders as $subfolder): ?>
                    <?php
                        $subfolderLangImages = getLangSpecificImagesForSubfolder($baseDir, $folder, $subfolder);
                        $hasSubfolderLang = !empty($subfolderLangImages);
                    ?>
                    <li class="subfolder-section">
                        <div class="subfolder-heading collapsed" onclick="toggleSection(this)">
                            <span><?php echo htmlspecialchars($subfolder); ?><?php if ($hasSubfolderLang): ?><?php endif; ?></span>
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
                                            $templateUrl = rtrim($baseUrl, '/') . '/' . $folder . '/' . $subfolder . '/_lang_/' . basename($file);
                                            $previewUrl = str_replace('_lang_', $lang, $templateUrl);
                                        ?>
                                        <li class="image-item">
                                            <img src="<?php echo htmlspecialchars($previewUrl); ?>" alt="<?php echo htmlspecialchars(basename($file)); ?>" data-full-url="<?php echo htmlspecialchars($templateUrl); ?>">
                                            <a href="<?php echo htmlspecialchars($templateUrl); ?>" title="<?php echo htmlspecialchars(basename($file)); ?>">
                                                <?php echo htmlspecialchars(basename($file)); ?> (eng)
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