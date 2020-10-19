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
/**
 * Save configuration settings in the config file and modVars
 *
 * @author jsb <jsb@xaraya.com>
 * @access public
 * @param $args['config'] array of config labels and values
 * @throws FUNCTION_FAILED
 */
function xarcachemanager_adminapi_save_cachingconfig($args)
{
    extract($args);

    if (!xarSecurityCheck('AdminXarCache')) { return; }
    if (empty($configSettings) || !is_array($configSettings)) { return FALSE; }

    if (!isset($cachingConfigFile)) {
         $cachingConfigFile = sys::varpath() . '/cache/config.caching.php';
    }

    if (!is_writable($cachingConfigFile)) {
        $msg=xarML('The caching configuration file is not writable by the web server.
                   #(1) must be writable by the web server for
                   the output caching to be managed by xarCacheManager.', $cachingConfigFile);
        throw new Exception($msg);
        return false;
    }

    $cachingConfig = join('', file($cachingConfigFile));

    // place the settings in modvars for safe keeping
    // and replace the cachingConfig with the new values
    foreach ($configSettings as $configKey => $configValue) {
        if (is_numeric($configValue)) {
            xarModVars::set('xarcachemanager', $configKey, $configValue);
            $cachingConfig = preg_replace('/\[\'' . $configKey . '\'\]\s*=\s*(|\")(.*)\\1;/', "['$configKey'] = $configValue;", $cachingConfig);
        } elseif (is_array($configValue)) {
            xarModVars::set('xarcachemanager', $configKey, 'array-' . serialize($configValue));
            $configValue = str_replace("'","\\'",$configValue);
            if (!empty($configValue)) {
                $keyslist = array_keys($configValue);
                // support basic associative array too
                if (!is_numeric($keyslist[0])) {
                    $keyValue = array();
                    foreach ($keyslist as $key) {
                        $keyValue[] = $key . "' => '" . $configValue[$key];
                    }
                    $configValue = "'" . implode("','",$keyValue) . "'";
                } else {
                    $configValue = "'" . implode("','",$configValue) . "'";
                }
            } else {
                $configValue = "'" . implode("','",$configValue) . "'";
            }
            $cachingConfig = preg_replace('/\[\'' . $configKey . '\'\]\s*=\s*array\s*\((.*)\)\s*;/i', "['$configKey'] = array($configValue);", $cachingConfig);
        } else {
            xarModVars::set('xarcachemanager', $configKey, $configValue);
            $configValue = str_replace("'","\\'",$configValue);
            $cachingConfig = preg_replace('/\[\'' . $configKey . '\'\]\s*=\s*(\'|\")(.*)\\1;/', "['$configKey'] = '$configValue';", $cachingConfig);
        }
    }

    // write the new settings to the config file
    $fp = fopen ($cachingConfigFile, 'wb');
    fwrite ($fp, $cachingConfig);
    fclose ($fp);

}

?>
