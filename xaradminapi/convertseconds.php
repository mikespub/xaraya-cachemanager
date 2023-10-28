<?php
/**
 * Update the configuration parameters
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage xarCacheManager module
 * @link http://xaraya.com/index.php/release/1652.html
 */
sys::import('modules.xarcachemanager.class.utility');
use Xaraya\Modules\CacheManager\CacheUtility;

/**
 * Update the configuration parameters of the module based on data from the modification form
 *
 * @author Jon Haworth
 * @author jsb <jsb@xaraya.com>
 * @access public
 * @uses CacheUtility::convertseconds()
 * @param string $args['starttime'] (seconds or hh:mm:ss)
 * @param string $args['direction'] (from or to)
 * @return string $convertedtime (hh:mm:ss or seconds)
 */
function xarcachemanager_adminapi_convertseconds($args)
{
    return CacheUtility::convertseconds($args);
}
