<?php
/**
 * construct an array of output cache subdirectories
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage CacheManager module
 * @link http://xaraya.com/index.php/release/1652.html
 * @author jsb
 */
sys::import('modules.cachemanager.class.manager');

/**
 * construct an array of output cache subdirectories
 *
 * @param $dir directory to start the search for subdirectories in
 * @return array sorted array of cache sub directories, with key set to directory name and value set to path
 * @todo do not include empty directories in the array
 */
function cachemanager_adminapi_getcachedirs($dir = false)
{
    $cachedirs = [];

    if ($dir && is_dir($dir)) {
        if (substr($dir, -1) != "/") {
            $dir .= "/";
        }
        if ($dirId = opendir($dir)) {
            while (($item = readdir($dirId)) !== false) {
                if ($item[0] != '.') {
                    if (is_dir($dir . $item)) {
                        $cachedirs[$item] = $dir . $item;
                        $cachedirs = array_merge($cachedirs, cachemanager_adminapi_getcachedirs($dir . $item));
                    }
                }
            }
            closedir($dirId);
        }
    }
    asort($cachedirs);
    return $cachedirs;
}
