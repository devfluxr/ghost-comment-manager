<?php
namespace Devfluxr\Gcmgr\Support;

if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Builds a PSR-4 classmap for known prefixes by scanning directories.
 * We assume PSR-4 compliance: Namespace\Sub\Class -> baseDir/Sub/Class.php
 */
class ClassmapBuilder
{
    /** @param array<string, string> $prefixDirs e.g. ['Inc\\' => '/path/inc'] */
    public static function build(array $prefixDirs): array
    {
        $map = [];

        foreach ($prefixDirs as $prefix => $baseDir) {
            $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR);
            if (!is_dir($baseDir)) { continue; }

            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($it as $file) {
                /** @var \SplFileInfo $file */
                if (!$file->isFile()) { continue; }
                if (substr($file->getFilename(), -4) !== '.php') { continue; }

                $realPath = $file->getRealPath();
                if (!$realPath) { continue; }

                // derive class name from path relative to baseDir
                $relative = ltrim(str_replace($baseDir, '', $realPath), DIRECTORY_SEPARATOR);
                $class    = $prefix . str_replace(DIRECTORY_SEPARATOR, '\\', substr($relative, 0, -4));

                // normalize backslashes
                $class = ltrim($class, '\\');
                $map[$class] = $realPath;
            }
        }

        return $map;
    }
}
