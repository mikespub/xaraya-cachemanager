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
use Xaraya\Modules\CacheManager\CacheInfo;
use Xaraya\Modules\MethodClass;
use xarVar;
use xarController;
use xarSecurity;
use xarSec;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * cachemanager admin view function
 * @extends MethodClass<AdminGui>
 */
class ViewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * show the content of cache items
     * @param array<mixed> $args with optional arguments:
     * - string $args['tab']
     * - string $args['key']
     * - string $args['code']
     * @return array|void
     * @see AdminGui::view()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!$this->fetch('tab', 'str', $tab, null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('key', 'str', $key, null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('code', 'str', $code, null, xarVar::NOT_REQUIRED)) {
            return;
        }

        if (empty($tab)) {
            $this->redirect($this->getUrl('admin', 'stats'));
            return;
        }
        if (empty($key)) {
            $this->redirect($this->getUrl(
                'admin',
                'stats',
                ['tab' => $tab]
            ));
            return;
        }

        if (!$this->checkAccess('AdminXarCache')) {
            return;
        }

        $data = [];

        $data['tab'] = $tab;
        $data['key'] = $key;
        $data['code'] = $code;
        $data['lines'] = [];
        $data['title']  = '';
        $data['link']  = '';
        $data['styles'] = [];
        $data['script'] = [];

        $cacheInfo = CacheInfo::getCache($tab);
        $cacheInfo->setContext($this->getContext());
        $content = $cacheInfo->getItem($key, $code);
        if (!empty($content)) {
            if ($tab == 'module' || $tab == 'object') {
                $data['lines']  = explode("\n", $content['output']);
                $data['title']  = $content['title'];
                $data['link']   = $content['link'];
                $data['styles'] = $content['styles'];
                $data['script'] = $content['script'];
            } elseif ($tab == 'variable') {
                $data['lines']  = explode("\n", print_r($content, true));
            } else {
                $data['lines'] = explode("\n", $content);
            }
        }

        // Generate a one-time authorisation code for this operation
        $data['authid'] = $this->genAuthKey();
        $data['return_url'] = $this->getUrl( 'admin', 'stats', ['tab' => $tab]);
        return $data;
    }
}