<?php
/**
 * Delete entry for a module item
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage CacheManager module
 * @link http://xaraya.com/index.php/release/1652.html
 */
sys::import('modules.cachemanager.class.hooks');
use Xaraya\Modules\CacheManager\CacheHooks;

/**
 * delete entry for a module item - hook for ('item','delete','API')
 *
 * @uses CacheHooks::deletehook()
 * @param array $args with mandatory arguments:
 * - int   $args['objectid'] ID of the object
 * - array $args['extrainfo'] extra information
 * @return array updated extrainfo array
 */
function cachemanager_adminapi_deletehook($args, $context = null)
{
    return CacheHooks::deletehook($args, $context);
}
