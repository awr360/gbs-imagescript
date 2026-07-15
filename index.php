<?php
// Array of three-letter language codes to hide as separate folders
$languageCodes = [
    'eng', 'mlg', 'spa', 'deu', 'fra', 'ita', 'por', 'rus', 'chi', 'jpn', 'kor',
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
$baseUrl = getenv('APP_BASE_URL') ?: '/images/';
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

function getAllowedExtensions() {
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'epub', 'zip'];
}

function isPreviewableExtension($extension) {
    return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
}

function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function getAbsoluteImageUrl($baseUrl, $path) {
    $normalizedBase = trim($baseUrl);
    if ($normalizedBase === '') {
        return '/' . ltrim($path, '/');
    }

    if (preg_match('#^https?://#i', $normalizedBase) || strpos($normalizedBase, '//') === 0) {
        return rtrim($normalizedBase, '/') . '/' . ltrim($path, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . '/' . ltrim(rtrim($normalizedBase, '/') . '/' . ltrim($path, '/'), '/');
}

// Function to get subfolders for a specific top-level folder
function getSubfoldersForFolder($baseDir, $folder) {
    $subfolders = [];
    $folderPath = $baseDir . '/' . $folder;
    
    if (!is_dir($folderPath)) {
        return $subfolders;
    }
    
    $iterator = new DirectoryIterator($folderPath);
    foreach ($iterator as $item) {
        if ($item->isDir() && !$item->isDot()) {
            $subfolderName = $item->getFilename();
            // Skip language folders identified by 3-letter codes (e.g. eng, mlg)
            if (!preg_match('/^[a-z]{3}$/i', $subfolderName)) {
                $subfolders[] = $subfolderName;
            }
        }
    }
    // Natural sort so names like ubp-1, ubp-2, ubp-10 sort as expected
    usort($subfolders, 'strnatcasecmp');
    
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
        if ($item->isFile() && in_array(strtolower($item->getExtension()), getAllowedExtensions(), true)) {
            $images[] = $item->getFilename();
        }
    }
    
    return $images;
}

// Function to get language-specific images for a specific subfolder
function getLangSpecificImagesForSubfolder($baseDir, $folder, $subfolder) {
    $langImages = [];
    $subfolderPath = $baseDir . '/' . $folder . '/' . $subfolder;

    if (!is_dir($subfolderPath)) {
        return $langImages;
    }

    // Detect language subfolders by scanning directories and matching 3-letter names
    $iterator = new DirectoryIterator($subfolderPath);
    foreach ($iterator as $item) {
        if ($item->isDir() && !$item->isDot()) {
            $name = $item->getFilename();
            if (preg_match('/^[a-z]{3}$/i', $name)) {
                $langPath = $subfolderPath . '/' . $name;
                $fileIter = new DirectoryIterator($langPath);
                foreach ($fileIter as $file) {
                    if ($file->isFile() && in_array(strtolower($file->getExtension()), getAllowedExtensions(), true)) {
                        $langImages[$name][] = $folder . '/' . $subfolder . '/' . $name . '/' . $file->getFilename();
                    }
                }
            }
        }
    }

    return $langImages;
}

// Get the top-level folders
$topLevelFolders = getTopLevelFolders($baseDir);

// Function to get all language folders in a subfolder
function getLanguageFoldersInSubfolder($baseDir, $folder, $subfolder) {
    $languages = [];
    $subfolderPath = $baseDir . '/' . $folder . '/' . $subfolder;
    
    if (!is_dir($subfolderPath)) {
        return $languages;
    }
    
    $iterator = new DirectoryIterator($subfolderPath);
    foreach ($iterator as $item) {
        if ($item->isDir() && !$item->isDot()) {
            $name = $item->getFilename();
            if (preg_match('/^[a-z]{3}$/i', $name)) {
                $languages[] = $name;
            }
        }
    }
    
    sort($languages);
    return $languages;
}

// Function to get images for a specific language folder
function getImagesInLanguageFolder($folderPath) {
    $images = [];
    
    if (!is_dir($folderPath)) {
        return $images;
    }
    
    $iterator = new DirectoryIterator($folderPath);
    foreach ($iterator as $item) {
        if ($item->isFile() && in_array(strtolower($item->getExtension()), getAllowedExtensions(), true)) {
            $images[] = $item->getFilename();
        }
    }
    
    sort($images);
    return $images;
}

function getAvailableLanguages($baseDir, $topLevelFolders) {
    $languages = [];

    foreach ($topLevelFolders as $folder) {
        $subfolders = getSubfoldersForFolder($baseDir, $folder);
        foreach ($subfolders as $subfolder) {
            $subfolderPath = $baseDir . '/' . $folder . '/' . $subfolder;
            if (!is_dir($subfolderPath)) {
                continue;
            }

            $iterator = new DirectoryIterator($subfolderPath);
            foreach ($iterator as $item) {
                if ($item->isDir() && !$item->isDot()) {
                    $name = $item->getFilename();
                    if (preg_match('/^[a-z]{3}$/i', $name)) {
                        $languages[$name] = true;
                    }
                }
            }
        }
    }

    $result = array_keys($languages);
    sort($result);
    return $result;
}

// Get current tab from query parameter
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'gallery';
$requestedLanguage = isset($_GET['lang']) ? strtolower(trim($_GET['lang'])) : '';
$availableLanguages = getAvailableLanguages($baseDir, $topLevelFolders);

if (in_array($requestedLanguage, $availableLanguages, true)) {
    $currentLanguage = $requestedLanguage;
} elseif (in_array('eng', $availableLanguages, true)) {
    $currentLanguage = 'eng';
} else {
    $currentLanguage = '';
}

// Output HTML
?>
<html>
<head>
    <title>Image List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 12px 16px;
        }
        h1 {
            color: #333;
            margin: 0;
        }
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 12px;
            border-bottom: 2px solid #ddd;
        }
        .tab-button {
            padding: 10px 16px;
            background-color: #e9ecef;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: #495057;
            border-radius: 4px 4px 0 0;
            text-decoration: none;
            display: inline-block;
        }
        .tab-button:hover {
            background-color: #dee2e6;
        }
        .tab-button.active {
            background-color: #007bff;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            margin: 20px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th {
            background-color: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .status-present {
            color: #28a745;
            font-weight: bold;
        }
        .status-missing {
            color: #dc3545;
            font-weight: bold;
        }
        .status-icon {
            font-size: 18px;
        }
        .subfolder-title {
            background-color: #e9ecef;
            padding: 15px;
            margin: 20px 0 10px 0;
            border-radius: 4px;
            font-size: 18px;
            font-weight: bold;
            color: #495057;
        }
        .top-level-title {
            background-color: #007bff;
            color: white;
            padding: 15px;
            margin: 20px 0 10px 0;
            border-radius: 4px;
            font-size: 20px;
            font-weight: bold;
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
        .file-placeholder {
            width: 150px;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
            color: #333;
            font-size: 12px;
            text-align: center;
            padding: 10px;
            box-sizing: border-box;
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
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .language-selector-form {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: white;
            padding: 6px 10px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .language-selector-form label {
            font-weight: bold;
            color: #495057;
        }
        .language-selector-form select {
            padding: 6px 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
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
    <div class="page-header">
        <h1>Global Bible School Image Gallery</h1>
        <form method="get" class="language-selector-form">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <label for="language-select">Language</label>
            <select id="language-select" name="lang" onchange="this.form.submit()">
                <option value="" <?php echo $currentLanguage === '' ? 'selected' : ''; ?>>Select language</option>
                <?php foreach ($availableLanguages as $language): ?>
                    <option value="<?php echo htmlspecialchars($language); ?>" <?php echo $currentLanguage === $language ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(strtoupper($language)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="tabs">
        <a href="?tab=gallery" class="tab-button <?php echo $currentTab === 'gallery' ? 'active' : ''; ?>">Gallery View</a>
        <a href="?tab=status" class="tab-button <?php echo $currentTab === 'status' ? 'active' : ''; ?>">Image Status</a>
    </div>

    <!-- Gallery Tab Content -->
    <div class="tab-content <?php echo $currentTab === 'gallery' ? 'active' : ''; ?>" id="gallery-tab">
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
                <?php foreach ($folderImages as $image):
                    $url = getAbsoluteImageUrl($baseUrl, $folder . '/' . $image);
                    $extension = getFileExtension($image);
                    $previewable = isPreviewableExtension($extension);
                ?>
                    <li class="image-item">
                        <?php if ($previewable): ?>
                            <img src="<?php echo htmlspecialchars($url); ?>" alt="<?php echo htmlspecialchars($image); ?>" data-copy-url="<?php echo htmlspecialchars($url); ?>">
                        <?php else: ?>
                            <div class="file-placeholder"><?php echo strtoupper(htmlspecialchars($extension)); ?></div>
                        <?php endif; ?>
                        <a href="<?php echo htmlspecialchars($url); ?>" title="<?php echo htmlspecialchars($image); ?>">
                            <?php echo htmlspecialchars($image); ?>
                        </a>
                    </li>
                <?php endforeach; ?>

                <!-- Subfolders -->
                <?php foreach ($subfolders as $subfolder): ?>
                    <?php
                        $subfolderPath = $folderPath . '/' . $subfolder;
                        $rootImages = getImagesInFolder($subfolderPath, $baseUrl);
                        $displayImages = [];
                        $selectedLanguageImages = [];
                        $hasSelectedLanguageImages = false;

                        foreach ($rootImages as $image) {
                            $displayImages[$image] = [
                                'name' => $image,
                                'label' => $image,
                                'isLanguageImage' => false,
                            ];
                        }

                        if ($currentLanguage !== '') {
                            $selectedLanguageImages = getImagesInLanguageFolder($subfolderPath . '/' . $currentLanguage);
                            $hasSelectedLanguageImages = !empty($selectedLanguageImages);

                            if ($hasSelectedLanguageImages) {
                                foreach ($selectedLanguageImages as $image) {
                                    $displayImages[$image] = [
                                        'name' => $image,
                                        'label' => $image . ' (' . $currentLanguage . ')',
                                        'isLanguageImage' => true,
                                    ];
                                }
                            }
                        }

                        $displayImages = array_values($displayImages);
                        usort($displayImages, function ($a, $b) {
                            return strnatcasecmp($a['name'], $b['name']);
                        });
                    ?>
                    <li class="subfolder-section">
                        <div class="subfolder-heading collapsed" onclick="toggleSection(this)">
                            <span><?php echo htmlspecialchars($subfolder); ?></span>
                            <span class="toggle-icon"></span>
                        </div>
                        <ul class="subfolder-gallery">
                            <?php foreach ($displayImages as $imageData):
                                $image = $imageData['name'];
                                $extension = getFileExtension($image);
                                $label = $imageData['label'];

                                $languagePath = '';
                                if ($currentLanguage !== '') {
                                    $languageFilePath = $baseDir . '/' . $folder . '/' . $subfolder . '/' . $currentLanguage . '/' . $image;
                                    if (file_exists($languageFilePath) || $imageData['isLanguageImage']) {
                                        $languagePath = $currentLanguage;
                                    }
                                }

                                if ($languagePath !== '') {
                                    $previewUrl = getAbsoluteImageUrl($baseUrl, $folder . '/' . $subfolder . '/' . $currentLanguage . '/' . $image);
                                    $linkUrl = getAbsoluteImageUrl($baseUrl, $folder . '/' . $subfolder . '/_lang_/' . $image);
                                } else {
                                    $previewUrl = getAbsoluteImageUrl($baseUrl, $folder . '/' . $subfolder . '/' . $image);
                                    $linkUrl = $previewUrl;
                                }
                                $previewable = isPreviewableExtension($extension);
                            ?>
                                <li class="image-item">
                                    <?php if ($previewable): ?>
                                        <img src="<?php echo htmlspecialchars($previewUrl); ?>" alt="<?php echo htmlspecialchars($label); ?>" data-copy-url="<?php echo htmlspecialchars($linkUrl); ?>">
                                    <?php else: ?>
                                        <div class="file-placeholder"><?php echo strtoupper(htmlspecialchars($extension)); ?></div>
                                    <?php endif; ?>
                                    <a href="<?php echo htmlspecialchars($linkUrl); ?>" title="<?php echo htmlspecialchars($label); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
    </div>

    <!-- Status Tab Content -->
    <div class="tab-content <?php echo $currentTab === 'status' ? 'active' : ''; ?>" id="status-tab">
    <?php foreach ($topLevelFolders as $folder): ?>
        <div class="top-level-title"><?php echo htmlspecialchars($folder); ?></div>
        <?php
            $subfolders = getSubfoldersForFolder($baseDir, $folder);
            foreach ($subfolders as $subfolder):
                $subfolderPath = $baseDir . '/' . $folder . '/' . $subfolder;
                $engPath = $subfolderPath . '/eng';
                $engImages = getImagesInLanguageFolder($engPath);
                
                if (empty($engImages)) {
                    continue;
                }
                
                $allLanguages = getLanguageFoldersInSubfolder($baseDir, $folder, $subfolder);
                $otherLanguages = array_diff($allLanguages, ['eng']);
                $otherLanguages = array_values($otherLanguages);
                sort($otherLanguages);
        ?>
            <div class="subfolder-title"><?php echo htmlspecialchars($subfolder); ?></div>
            <table>
                <thead>
                    <tr>
                        <th>Image Name (eng)</th>
                        <?php foreach ($otherLanguages as $lang): ?>
                            <th><?php echo htmlspecialchars($lang); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($engImages as $image): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($image); ?></td>
                            <?php foreach ($otherLanguages as $lang): ?>
                                <?php
                                    $langPath = $subfolderPath . '/' . $lang . '/' . $image;
                                    $exists = file_exists($langPath);
                                ?>
                                <td>
                                    <?php if ($exists): ?>
                                        <span class="status-icon status-present">✓</span>
                                    <?php else: ?>
                                        <span class="status-icon status-missing">✗</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    <?php endforeach; ?>
    </div>

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
                    currentUrl = this.dataset.copyUrl || this.dataset.fullUrl || new URL(this.currentSrc || this.src, window.location.href).href;
                    lightboxImg.src = this.currentSrc || this.src;
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
                navigator.clipboard.writeText(currentUrl || lightboxImg.currentSrc || lightboxImg.src || window.location.href);
            });
            document.querySelectorAll('.image-item a').forEach(a => {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    navigator.clipboard.writeText(this.href || this.dataset.copyUrl || this.dataset.fullUrl || '');
                });
            });
        });
    </script>
    <div id="lightbox" class="lightbox"><img id="lightbox-img" src="" alt=""></div>
</body>
</html>
<?php
?>
