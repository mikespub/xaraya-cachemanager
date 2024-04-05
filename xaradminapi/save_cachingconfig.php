<?php
/**
 * Save config
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage xarCacheManager module
 * @link http://xaraya.com/index.php/release/1652.html
 */
sys::import('modules.xarcachemanager.class.manager');
use Xaraya\Modules\CacheManager\CacheManager;

/**
 * Save configuration settings in the config file and modVars
 *
 * @author jsb <jsb@xaraya.com>
 * @access public
 * @uses CacheManager::save_config()
 * @param array $args
 * @param $args['config'] array of config labels and values
 */
function xarcachemanager_adminapi_save_cachingconfig(array $args = [], $context = null)
{
    return CacheManager::save_config($args);
}
