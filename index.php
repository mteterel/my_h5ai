<?php
require_once './.my_h5ai/vendor/autoload.php';
require_once './.my_h5ai/H5AITwigExtension.php';

/**
 * Returns the list of directories and sub-directories of a given folder
 * @param string $base_path
 * @param string $path
 * @return array
 */
function h5ai_get_directories(string $base_path, string $path = '')
{
    $result = [];
    if (($handle = opendir($base_path . $path)) !== false) {
        while (($entry = readdir($handle)) !== false) {
            if ($entry[0] === '.')
                continue;

            if (is_dir($base_path . $path . '/' . $entry)
                && $entry !== '.' && $entry !== '..') {
                $result[] = [
                    'name' => $entry,
                    'full_path' => $path . '/' . $entry . '/',
                    'dirs' => h5ai_get_directories($base_path, $path . '/' . $entry)];
            }
        }
        closedir($handle);
    }
    uasort($result, function($a, $b) {
        return $a['name'] > $b['name'];
    });
    return $result;
}

/**
 * Returns informations about every file/directory within a folder
 * @param string $path
 * @return array
 */
function h5ai_get_dir_contents(string $path)
{
    $result = [];
    if (($handle = opendir($path)) !== false) {
        while(($entry = readdir($handle)) !== false) {
            $full_path = $path . '/' . $entry;
            if (is_file($full_path)) {
                if ($entry[0] === '.')
                    continue;
                $result[$entry]['name'] = $entry;
                $result[$entry]['type'] = 'file';
                $result[$entry]['mtime'] = filemtime($full_path);
                $result[$entry]['size_unformatted'] = filesize($full_path);
                $result[$entry]['size'] = h5ai_format_filesize($result[$entry]['size_unformatted']);
            }
            elseif (is_dir($full_path) && $entry !== '.') {
                if ($entry[0] === '.' && $entry != '..')
                    continue;
                $result[$entry]['name'] = $entry;
                $result[$entry]['type'] = 'directory';
                $result[$entry]['mtime'] = filemtime($full_path);
                if ($entry !== '..') {
                    $result[$entry]['size_unformatted'] = h5ai_get_dir_size($full_path);
                    $result[$entry]['size'] = h5ai_format_filesize($result[$entry]['size_unformatted']);
                }
            }
        }
        closedir($handle);
    }
    usort($result, function($a, $b) {
        return $a['type'] !== $b['type'] ? ($b['type'] == 'directory') : ($a['name'] > $b['name']);
    });
    return $result;
}


/**
 * Returns the size of the contents of a directory
 * @param string $directory
 * @return false|int
 */
function h5ai_get_dir_size(string $directory)
{
    $size = 0;
    if (($handle = opendir($directory)) !== false) {
        while (($entry = readdir($handle)) !== false) {
            $path = $directory . '/' . $entry;
            if (is_dir($path) && $entry !== '.' && $entry !== '..')
                $size += h5ai_get_dir_size($path);
            elseif (is_file($path))
                $size += filesize($path);
        }
    }
    return $size;
}

/**
 * Converts a size in bytes to its representation in a closer unit
 * @param int $size
 * @return string
 */
function h5ai_format_filesize(int $size)
{
    $units = array( ' bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return number_format($size / pow(1024, $power)). $units[$power];
}

// Get the absolute directory that contains the current script.
$script_dir = dirname(__FILE__);

// Get its relative path to the DOCUMENT_ROOT.
$relative_dir = str_replace($_SERVER['DOCUMENT_ROOT'], '', $script_dir);

// Get the directory tree starting from the script's directory.
$dir_tree = h5ai_get_directories($_SERVER['DOCUMENT_ROOT'], $relative_dir);

// Get the directory content of the path passed in URL.
$dir_contents = h5ai_get_dir_contents($script_dir . $_SERVER['PATH_INFO']);

// Load the Twig environment & instantiates extension
$loader = new Twig\Loader\FilesystemLoader('./.my_h5ai/templates/');
$twig = new Twig\Environment($loader, ['cache' => false ]);
$twig->addExtension(new H5AITwigExtension());

// Variables passed to template:
// request_path -- The path passed to the script's URL (ex. index.php/my/directory/ => /my/directory/)
// directory_tree -- The directory tree displayed on the left
// current_dir -- The content of the current directory
// path_breadcrumb -- An array of every parent directory up to the current one
// relative_script_dir -- The relative path between DOCUMENT_ROOT and the script. Used for URL generation.
echo $twig->render('index.html.twig', [
    'request_path' => $_SERVER['PATH_INFO'],
    'directory_tree' => $dir_tree,
    'current_dir' => $dir_contents,
    'path_breadcrumb' => explode('/', trim($_SERVER['PATH_INFO'], '/')),
    'relative_script_dir' => $relative_dir
]);