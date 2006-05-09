<?php
/**
 * Flush the appropriate cache
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage xarCacheManager module
 * @link http://xaraya.com/index.php/release/1652.html
 */
/**
 * flush the appropriate cache when a module item is created- hook for ('item','create','API')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return array extrainfo array
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 * @todo - actually raise errors, get intelligent and specific about cache files to remove
 */
function xarcachemanager_adminapi_createhook($args)
{
    extract($args);

    $outputCacheDir = xarCoreGetVarDirPath() . '/cache/output/';

    if (!function_exists('xarOutputFlushCached')) {
        // caching is on, but the function isn't available
        // load xarCache to make it so
        include_once 'includes/xarCache.php';
        if (xarCache_init(array('cacheDir' => $outputCacheDir)) == false) {
            // somethings wrong, caching should be off now
            return;
        }
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object ID', 'admin', 'createhook', 'xarcachemanager');
        xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $extrainfo = array();
    }

    // When called via hooks, modname wil be empty, but we get it from the
    // extrainfo or the current module
    if (empty($modname)) {
        if (!empty($extrainfo['module'])) {
            $modname = $extrainfo['module'];
        } else {
            $modname = xarModGetName();
        }
    }
    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'createhook', 'xarcachemanager');
        xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
         if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
             $itemtype = $extrainfo['itemtype'];
         } else {
             $itemtype = 0;
         }
    }

    // TODO: make all the module cache flushing behavior admin configurable

    switch($modname) {
        case 'blocks':
            // blocks could be anywhere, we're not smart enough not know exactly where yet
            // so just flush all pages
            xarOutputFlushCached('', $outputCacheDir . 'page');
            break;
        case 'privileges': // fall-through all modules that should flush the entire cache
        case 'roles':
            // if security changes, flush everything, just in case.
            xarOutputFlushCached('');
            break;
        case 'articles':
            if (isset($extrainfo['status']) && $extrainfo['status'] == 0) {
                break;
            }
            xarOutputFlushCached('articles-');
            // a status update might mean a new menulink and new base homepage
            xarOutputFlushCached('base');
            break;
        case 'autolinks': // fall-through all hooked utility modules that are admin modified
        case 'categories': // keep falling through
        case 'html': // keep falling through
        case 'keywords': // keep falling through
                     // delete cachekey of each module autolinks is hooked to.
            $hooklist = xarModAPIFunc('modules','admin','gethooklist');
            $modhooks = reset($hooklist[$modname]);

            foreach ($modhooks as $hookedmodname => $hookedmod) {
                $cacheKey = "$hookedmodname-";
                xarOutputFlushCached($cacheKey);
            }
                // no break because we want it to keep going and flush it's own cacheKey too
                // incase it's got a user view, like categories.
        default:
            // identify pages that include the updated item and delete the cached files
            // nothing fancy yet, just flush it out
            $cacheKey = "$modname-";
            xarOutputFlushCached($cacheKey);
            // a new item might mean a new menulink
            xarOutputFlushCached('base-block');
            break;
    }

    if (xarModGetVar('xarcachemanager','AutoRegenSessionless')) {
        xarModAPIFunc( 'xarcachemanager', 'admin', 'regenstatic');
    }

    return $extrainfo;
}

?>
