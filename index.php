<?php
require_once './.my_h5ai/vendor/autoload.php';
require_once './.my_h5ai/H5AITwigExtension.php';

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
                $result[$entry]['size'] = h5ai_format_filesize(filesize($full_path));
            }
            elseif (is_dir($full_path) && $entry !== '.') {
                if ($entry[0] === '.' && $entry != '..')
                    continue;
                $result[$entry]['name'] = $entry;
                $result[$entry]['type'] = 'directory';
                $result[$entry]['mtime'] = filemtime($full_path);
                if ($entry !== '..')
                    $result[$entry]['size'] = h5ai_format_filesize(h5ai_get_dir_size($full_path));
            }
        }
        closedir($handle);
    }
    usort($result, function($a, $b) {
        return ($a['name'] > $b['name']) || $a['type'] !== 'directory' && $b['type'] === 'directory';
    });
    return $result;
}

function h5ai_get_dir_size($directory)
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

function h5ai_format_filesize(int $size)
{
    $units = array( 'bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return number_format($size / pow(1024, $power), $size < 1024 ? 0 : 2, '.', ',') . ' ' . $units[$power];
}

// ===
$script_dir = dirname(__FILE__);
$relative_dir = str_replace($_SERVER['DOCUMENT_ROOT'], '', $script_dir);
$dir_tree = h5ai_get_directories($_SERVER['DOCUMENT_ROOT'], $relative_dir);
$dir_contents = h5ai_get_dir_contents($script_dir . $_SERVER['PATH_INFO']);
// ===

$loader = new Twig\Loader\FilesystemLoader('./.my_h5ai/templates/');
$twig = new Twig\Environment($loader, [
    'cache' => false ]);
$twig->addExtension(new H5AITwigExtension());
echo $twig->render('index.html.twig', [
    'request_path' => $_SERVER['PATH_INFO'],
    'directory_tree' => $dir_tree,
    'current_dir' => $dir_contents,
    'path_breadcrumb' => explode('/', trim($_SERVER['PATH_INFO'], '/')),
    'relative_script_dir' => $relative_dir . '/'
]);