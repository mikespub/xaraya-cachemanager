<?php
/**
 * Construct an array of current cache info
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage CacheManager module
 * @link http://xaraya.com/index.php/release/1652.html
 */
sys::import('modules.cachemanager.class.info');
use Xaraya\Modules\CacheManager\CacheInfo;

/**
 * Construct an array of the current cache info
 *
 * @author jsb
 * @uses CacheInfo::getInfo()
 * @param array $args['type'] cachetype to start the search for cacheinfo
 * @return array array of cacheinfo
*/
function cachemanager_adminapi_getcacheinfo(array $args = ['type' => ''], $context = null)
{
    $type = '';
    if (is_array($args)) {
        extract($args);
    } else {
        $type = $args;
    }
    return CacheInfo::getInfo($type);
}
