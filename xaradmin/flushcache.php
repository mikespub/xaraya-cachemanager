<?php
/**
 * Flush output cache
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage CacheManager module
 * @link http://xaraya.com/index.php/release/1652.html
 */
sys::import('modules.cachemanager.class.manager');
use Xaraya\Modules\CacheManager\CacheManager;

/**
 * Flush cache files for a given cacheKey
 * @author jsb
 * @param array $args with optional arguments:
 * - string $args['flushkey']
 * - string $args['cachecode']
 * - string $args['confirm']
 * @return array|void
 */
function cachemanager_admin_flushcache(array $args = [], $context = null)
{
    // Security Check
    if (!xarSecurity::check('AdminXarCache')) {
        return;
    }

    extract($args);

    if (!xarVar::fetch('flushkey', 'isset', $flushkey, '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('confirm', 'str:1:', $confirm, '', xarVar::NOT_REQUIRED)) {
        return;
    }

    $cachetypes = xarMod::apiFunc('cachemanager', 'admin', 'getcachetypes');

    if (empty($confirm)) {
        $data = [];

        $data['message']    = false;
        $data['cachetypes'] = $cachetypes;
        $data['cachekeys'] = [];
        foreach (array_keys($cachetypes) as $type) {
            $data['cachekeys'][$type] = xarMod::apiFunc('cachemanager', 'admin', 'getcachekeys', ['type' => $type]);
        }

        $data['instructions'] = xarMLS::translate("Please select a cache key to be flushed.");
        $data['instructionhelp'] = xarMLS::translate("All cached files of output associated with this key will be deleted.");

        // Generate a one-time authorisation code for this operation
        $data['authid'] = xarSec::genAuthKey();
    } else {
        // Confirm authorisation code.
        if (!xarSec::confirmAuthKey()) {
            return;
        }

        //Make sure their is an authkey selected
        if (empty($flushkey) || !is_array($flushkey)) {
            $data['notice'] = xarMLS::translate("You must select a cache key to flush.  If there is no cache key to select the output cache is empty.");
        } else {
            // Get the output cache directory so you can flush items even if output caching is disabled
            $outputCacheDir = xarCache::getOutputCacheDir();

            // get the caching config settings from the config file
            $data['settings'] = CacheManager::get_config(
                ['from' => 'file', 'tpl_prep' => true]
            );

            // see if we need to delete an individual item instead of flushing the key
            if (!xarVar::fetch('cachecode', 'isset', $cachecode, '', xarVar::NOT_REQUIRED)) {
                return;
            }

            $found = 0;
            foreach ($flushkey as $type => $key) {
                if (empty($key) || $key == '-') {
                    continue;
                }
                if ($key == '*') {
                    $key = '';
                }
                $upper = ucfirst($type);
                $storage = $upper . 'CacheStorage'; // e.g. BlockCacheStorage
                if (empty($data['settings'][$storage])) {
                    continue;
                }

                // get cache storage
                $cachestorage = xarCache::getStorage(['storage'  => $data['settings'][$storage],
                                                           'type'     => $type,
                                                           'cachedir' => $outputCacheDir, ]);
                if (empty($cachestorage)) {
                    continue;
                }

                if (!empty($key) && !empty($cachecode) && !empty($cachecode[$type])) {
                    $cachestorage->setCode($cachecode[$type]);
                    $cachestorage->delCached($key);
                } else {
                    $cachestorage->flushCached($key);
                }
                $found++;
            }
            if (empty($found)) {
                $data['notice'] = xarMLS::translate("You must select a cache key to flush.  If there is no cache key to select the output cache is empty.");
            } else {
                $data['notice'] = xarMLS::translate("The cached output for this key has been flushed.");
            }
        }

        if (!xarVar::fetch('return_url', 'isset', $return_url, null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!empty($return_url)) {
            xarController::redirect($return_url, null, $context);
            return;
        }

        $return_url = xarController::URL('cachemanager', 'admin', 'flushcache');
        $data['returnlink'] = ['url'   => $return_url,
                                    'title' => xarMLS::translate('Return to the cache key selector'),
                                    'label' => xarMLS::translate('Back'), ];

        $data['message'] = true;
    }

    $data['cachesize'] = [];
    foreach (array_keys($cachetypes) as $type) {
        $cachesize = xarMod::apiFunc('cachemanager', 'admin', 'getcachesize', ['type' => $type]);
        if (!empty($cachesize)) {
            $data['cachesize'][$type] = round($cachesize / 1048576, 2);
        } else {
            $data['cachesize'][$type] = '0.00';
        }
    }

    return $data;
}
