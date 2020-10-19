<?php
/**
 * Update entry for an item
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
 * update entry for a module item - hook for ('item','updateconfig','API')
 * Optional $extrainfo['xarcachemanager_remark'] from arguments, or 'xarcachemanager_remark' from input
 *
 * @param array $args with mandatory arguments:
 * - array $args['extrainfo'] extra information
 * @return array updated extrainfo array
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 * @todo - actually raise errors, get intelligent and specific about cache files to remove
 */
function xarcachemanager_adminapi_updateconfighook($args)
{
    extract($args);

    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $extrainfo = array();
    }

    if (!xarCache::$outputCacheIsEnabled) {
        // nothing more to do here
        return $extrainfo;
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
    $modid = xarMod::getRegId($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'updatehook', 'xarcachemanager');
        throw new Exception($msg);
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

        case 'base': // who knows what global impact a config change to base might make
            // flush everything.
            if (xarOutputCache::$pageCacheIsEnabled) {
                xarPageCache::flushCached('');
            }
            if (xarOutputCache::$blockCacheIsEnabled) {
                xarBlockCache::flushCached('');
            }
            break;
        case 'autolinks': // fall-through all hooked utility modules that are admin config modified
        case 'comments': // keep falling through
        case 'keywords': // keep falling through
            // delete cachekey of each module autolinks is hooked to.
            if (xarOutputCache::$pageCacheIsEnabled) {
                $hooklist = xarMod::apiFunc('modules','admin','gethooklist');
                $modhooks = reset($hooklist[$modname]);

                foreach ($modhooks as $hookedmodname => $hookedmod) {
                    $cacheKey = "$hookedmodname-";
                    xarPageCache::flushCached($cacheKey);
                }
            }
            // no break because we want it to keep going and flush it's own cacheKey too
            // incase it's got a user view, like categories.
        case 'articles': // fall-through
            //nothing special yet
        default:
            // identify pages that include the updated item and delete the cached files
            // nothing fancy yet, just flush it out
            $cacheKey = "$modname-";
            if (xarOutputCache::$pageCacheIsEnabled) {
                xarPageCache::flushCached($cacheKey);
            }
            // since we're modifying the config, we might get a new admin menulink
            if (xarOutputCache::$blockCacheIsEnabled) {
                xarBlockCache::flushCached('base-block');
            }
            break;
    }

    if (xarModVars::get('xarcachemanager','AutoRegenSessionless')) {
        xarMod::apiFunc( 'xarcachemanager', 'admin', 'regenstatic');
    }

    // Return the extra info
    return $extrainfo;
}

?>
