<?php

/**
 * update entry for a module item - hook for ('item','updateconfig','API')
 * Optional $extrainfo['xarcachemanager_remark'] from arguments, or 'xarcachemanager_remark' from input
 *
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 * todo - actually raise errors, get intelligent and specific about cache files to remove
 */
function xarcachemanager_adminapi_updateconfighook($args)
{
    extract($args);
    
    if (!file_exists(xarCoreGetVarDirPath() . '/cache/output/cache.touch')) {
        // caching is not enabled and xarCache will not be available
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
                    'module name', 'admin', 'updatehook', 'xarcachemanager');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
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

    switch($modname) {

        case 'base': // who knows what global impact a config change to base might make
            // flush everything.
            $cacheKey = "";
            xarPageFlushCached($cacheKey);
            break;
        case 'autolinks': // fall-through all hooked utility modules that are admin config modified
        case 'comments': // keep falling through
        case 'keywords': // keep falling through
            // delete cachekey of each module autolinks is hooked to.
            $hooklist = xarModAPIFunc('modules','admin','gethooklist');
            $modhooks = reset($hooklist[$modname]);

            foreach ($modhooks as $hookedmodname => $hookedmod) {
                $cacheKey = "$hookedmodname-";
                xarPageFlushCached($cacheKey);
            }
            // no break because we want it to keep going and flush it's own cacheKey too
            // incase it's got a user view, like categories.
        case 'articles': // fall-through
            //nothing special yet
        default:
            // identify pages that include the updated item and delete the cached files
            // nothing fancy yet, just flush it out
            $cacheKey = "$modname-";
            xarPageFlushCached($cacheKey);
            break;
    }

    // Return the extra info
    return $extrainfo;
}

?>
