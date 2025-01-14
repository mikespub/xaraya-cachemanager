<?php

/**
 * @package modules\cachemanager
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\CacheManager\AdminGui;

use Xaraya\Modules\CacheManager\AdminGui;
use Xaraya\Modules\CacheManager\AdminApi;
use Xaraya\Modules\CacheManager\CacheManager;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarMod;
use xarMLS;
use xarSec;
use xarCache;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * cachemanager admin flushcache function
 * @extends MethodClass<AdminGui>
 */
class FlushcacheMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Flush cache files for a given cacheKey
     * @author jsb
     * @param array<mixed> $args with optional arguments:
     * - string $args['flushkey']
     * - string $args['cachecode']
     * - string $args['confirm']
     * @return array|void
     * @see AdminGui::flushcache()
     */
    public function __invoke(array $args = [])
    {
        // Security Check
        if (!$this->checkAccess('AdminXarCache')) {
            return;
        }

        extract($args);

        if (!$this->fetch('flushkey', 'isset', $flushkey, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('confirm', 'str:1:', $confirm, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        $admingui = $this->getParent();

        /** @var AdminApi $adminapi */
        $adminapi = $admingui->getModule()->getAdminAPI();

        $cachetypes = $adminapi->getcachetypes();

        if (empty($confirm)) {
            $data = [];

            $data['message']    = false;
            $data['cachetypes'] = $cachetypes;
            $data['cachekeys'] = [];
            foreach (array_keys($cachetypes) as $type) {
                $data['cachekeys'][$type] = $adminapi->getcachekeys(['type' => $type]);
            }

            $data['instructions'] = $this->translate("Please select a cache key to be flushed.");
            $data['instructionhelp'] = $this->translate("All cached files of output associated with this key will be deleted.");

            // Generate a one-time authorisation code for this operation
            $data['authid'] = $this->genAuthKey();
        } else {
            // Confirm authorisation code.
            if (!$this->confirmAuthKey()) {
                return;
            }

            //Make sure their is an authkey selected
            if (empty($flushkey) || !is_array($flushkey)) {
                $data['notice'] = $this->translate("You must select a cache key to flush.  If there is no cache key to select the output cache is empty.");
            } else {
                // Get the output cache directory so you can flush items even if output caching is disabled
                $outputCacheDir = xarCache::getOutputCacheDir();

                // get the caching config settings from the config file
                $data['settings'] = CacheManager::get_config(
                    ['from' => 'file', 'tpl_prep' => true]
                );

                // see if we need to delete an individual item instead of flushing the key
                if (!$this->fetch('cachecode', 'isset', $cachecode, '', xarVar::NOT_REQUIRED)) {
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
                    $data['notice'] = $this->translate("You must select a cache key to flush.  If there is no cache key to select the output cache is empty.");
                } else {
                    $data['notice'] = $this->translate("The cached output for this key has been flushed.");
                }
            }

            if (!$this->fetch('return_url', 'isset', $return_url, null, xarVar::NOT_REQUIRED)) {
                return;
            }
            if (!empty($return_url)) {
                $this->redirect($return_url);
                return;
            }

            $return_url = $this->getUrl('admin', 'flushcache');
            $data['returnlink'] = ['url'   => $return_url,
                'title' => $this->translate('Return to the cache key selector'),
                'label' => $this->translate('Back'), ];

            $data['message'] = true;
        }

        $data['cachesize'] = [];
        foreach (array_keys($cachetypes) as $type) {
            $cachesize = $adminapi->getcachesize(['type' => $type]);
            if (!empty($cachesize)) {
                $data['cachesize'][$type] = round($cachesize / 1048576, 2);
            } else {
                $data['cachesize'][$type] = '0.00';
            }
        }

        return $data;
    }
}