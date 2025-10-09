<?php
namespace Devfluxr\Gcmgr\Support;

if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Lightweight PSR-4 autoloader with:
 * - Multi-namespace, multi-basedir support
 * - Classmap (array) support
 * - Optional APCu caching (define('GCM_AUTOLOADER_APCU', true))
 *
 * Usage:
 *   $loader = new Autoloader();
 *   $loader->addPsr4('Inc\\', GCM_PLUGIN_DIR . 'inc/');
 *   $loader->setClassMap($mapArray);
 *   $loader->register();
 */
class Autoloader
{
    /** @var array<string, array<int, string>> */
    protected $prefixes = [];

    /** @var array<string, string> */
    protected $classMap = [];

    /** @var bool */
    protected $apcu = false;

    /** @var string */
    protected $apcuPrefix = 'gcm.autoload.';

    public function __construct(?bool $apcuEnabled = null)
    {
        $this->apcu = ($apcuEnabled ?? (defined('GCM_AUTOLOADER_APCU') && GCM_AUTOLOADER_APCU))
            && function_exists('apcu_fetch')
            && (bool) ini_get('apc.enabled');
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Add PSR-4 mapping.
     * @param string $prefix   Namespace prefix, e.g. 'Inc\\'
     * @param string $baseDir  Absolute path to base directory
     * @param bool   $prepend  Prepend for higher priority
     */
    public function addPsr4(string $prefix, string $baseDir, bool $prepend = false): void
    {
        $prefix = ltrim($prefix, '\\');
        if (substr($prefix, -1) !== '\\') {
            $prefix .= '\\';
        }
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $baseDir);
        } else {
            $this->prefixes[$prefix][] = $baseDir;
        }
    }

    /** @param array<string, string> $map */
    public function setClassMap(array $map): void
    {
        // normalize slashes
        foreach ($map as $class => $file) {
            $this->classMap[ltrim($class, '\\')] = $file;
        }
    }

    public function loadClass(string $class): bool
    {
        $class = ltrim($class, '\\');

        // 1) Classmap first
        if (isset($this->classMap[$class])) {
            return $this->requireFile($this->classMap[$class]);
        }

        // 2) APCu cache
        if ($this->apcu) {
            $cacheKey = $this->apcuPrefix . $class;
            $hit = apcu_fetch($cacheKey);
            if (is_string($hit) && $hit !== '' && file_exists($hit)) {
                return $this->requireFile($hit);
            }
        }

        // 3) PSR-4 resolution
        $file = $this->findFile($class);
        if ($file && $this->requireFile($file)) {
            if ($this->apcu) {
                apcu_store($this->apcuPrefix . $class, $file, 3600);
            }
            return true;
        }

        return false;
    }

    protected function findFile(string $class): ?string
    {
        // Work down the namespace to find a mapped base dir
        $logical = str_replace('\\', '/', $class) . '.php';

        // Walk back the namespace separators to find a prefix
        $prefix = $class;
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relative = substr($logical, $pos + 1);

            if (isset($this->prefixes[$prefix])) {
                foreach ($this->prefixes[$prefix] as $baseDir) {
                    $path = $baseDir . $relative;
                    if (file_exists($path)) {
                        return $path;
                    }
                }
            }
            // Trim the trailing namespace separator for next iteration
            $prefix = rtrim($prefix, '\\');
        }

        // Fallback for classes without namespace (rare)
        if (isset($this->prefixes['\\'])) {
            foreach ($this->prefixes['\\'] as $baseDir) {
                $path = $baseDir . $logical;
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    protected function requireFile(string $file): bool
    {
        require_once $file;
        return true;
    }
}
