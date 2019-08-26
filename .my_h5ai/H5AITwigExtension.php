<?php

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class H5AITwigExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('file_icon', [$this, 'getFileIcon'])
        ];
    }

    public function getFileIcon(string $path) {
        $file_ext = pathinfo($path, PATHINFO_EXTENSION);

        switch($file_ext) {
            case 'php':
                return 'blue php';
            case 'js':
                return 'yellow js';
            case 'html':
            case 'htm':
                return 'orange html5';
            case 'json':
                return 'blue code';
            case 'css':
                return 'blue css3';
            case 'less':
            case 'sass':
                return 'sass';
            case 'vue':
                return 'green vuejs';
            case 'exe':
                return 'blue windows';
            case 'elf':
                return 'linux';
            case 'docx':
                return 'blue file word';
            case 'xlsx':
                return 'green file excel';
            case 'pptx':
                return 'brown file powerpoint';
            case 'ogg':
            case 'wmv':
            case 'wav':
            case 'mp3':
                return 'file audio outline';
            case 'zip':
            case 'tar':
                return 'yellow archive file';
            case 'pdf':
                return 'red pdf file';
            case 'mp4':
                return 'file video outline';
            default:
                return 'blue file outline';
        }
    }
}