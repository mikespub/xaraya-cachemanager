<?php
/**
 * Modify hook
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage xarCacheManager module
 * @link http://xaraya.com/index.php/release/1652.html
 */
/**
 * modify an entry for a module item - hook for ('item','modify','GUI')
 *
 * @param array $args with mandatory arguments:
 * - int   $args['objectid'] ID of the object
 * - array $args['extrainfo'] extra information
 * @return string hook output in HTML
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function xarcachemanager_admin_modifyhook($args)
{
    extract($args);

    if (!xarSecurityCheck('AdminXarCache', 0)) { return ''; }

    // Get the output cache directory so you can access it even if output caching is disabled
    $outputCacheDir = xarCache::getOutputCacheDir();

    // only display modify hooks if block level output caching has been enabled
    // (don't check if output caching is enabled here so config options can be tweaked
    //  even when output caching has been temporarily disabled)
    if (!xarOutputCache::$blockCacheIsEnabled) {
        return '';
    }

    if (!isset($extrainfo)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'modifyhook', 'changelog');
        throw new Exception($msg);
        return $msg;
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object ID', 'admin', 'modifyhook', 'changelog');
        throw new Exception($msg);
        return $msg;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    // we are only interested in the config of block output caching for now
    if ($modname !== 'blocks') {
        return '';
    }

    $modid = xarMod::getRegId($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'modifyhook', 'changelog');
        throw new Exception($msg);
        return $msg;
    }

    if (!empty($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = 0;
    }

    if (!empty($extrainfo['itemid']) && is_numeric($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } else {
        $itemid = $objectid;
    }

    $systemPrefix = xarDB::getPrefix();
    $blocksettings = $systemPrefix . '_cache_blocks';
    $dbconn = xarDB::getConn();
    $query = "SELECT nocache,
             page,
             theuser,
             expire
             FROM $blocksettings WHERE blockinstance_id = $itemid ";
    $result =& $dbconn->Execute($query);
    if ($result && !$result->EOF) {
        list ($noCache, $pageShared, $userShared, $blockCacheExpireTime) = $result->fields;
    } else {
        $noCache = 0;
        $pageShared = 0;
        $userShared = 0;
        $blockCacheExpireTime = null;
        // Get the instance details.
        $instance = xarModAPIfunc('blocks', 'user', 'get', array('bid' => $itemid));
        // Try loading some defaults from the block init array (cfr. articles/random)
        if (!empty($instance) && !empty($instance['module']) && !empty($instance['type'])) {
            $initresult = xarModAPIfunc('blocks', 'user', 'read_type_init',
                                        array('module' => $instance['module'],
                                              'type' => $instance['type']));
            if (!empty($initresult) && is_array($initresult)) {
                if (isset($initresult['nocache'])) {
                    $noCache = $initresult['nocache'];
                }
                if (isset($initresult['pageshared'])) {
                    $pageShared = $initresult['pageshared'];
                }
                if (isset($initresult['usershared'])) {
                    $userShared = $initresult['usershared'];
                }
                if (isset($initresult['cacheexpire'])) {
                    $blockCacheExpireTime = $initresult['cacheexpire'];
                }
            }
        }
    }
    if (!empty($blockCacheExpireTime)) {
        $blockCacheExpireTime = xarMod::apiFunc( 'xarcachemanager', 'admin', 'convertseconds',
                                               array('starttime' => $blockCacheExpireTime,
                                                     'direction' => 'from'));
    }
    return xarTplModule('xarcachemanager','admin','modifyhook',
                        array('noCache' => $noCache,
                              'pageShared' => $pageShared,
                              'userShared' => $userShared,
                              'cacheExpire' => $blockCacheExpireTime));
}

?>
