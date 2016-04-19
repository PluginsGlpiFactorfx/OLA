<?php
/**
 * @package     olas
 * @author      Cédric Denis, Gilles Dubois
 * @copyright   Copyright (c) 2010-2015 FactorFX, Linagora
 * @license     AGPL License 3.0 or (at your option) any later version
 *              http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link        https://www.factorfx.com
 * @link        http://www.linagora.com
 * @since       2015
 *
 * --------------------------------------------------------------------------
 */

function plugin_init_olas()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['olas'] = true;

    $PLUGIN_HOOKS['change_profile']['olas'] = array('PluginOlasProfile','initProfile');
    Plugin::registerClass('PluginOlasProfile', array('addtabon' => 'Profile'));

    $plugin = new Plugin();
    if ($plugin->isActivated('olas')) {
        $PLUGIN_HOOKS['reports']['olas'] = array();
        if (Session::haveRight("plugin_olas_sla", READ)) {
            olaAddMenu($PLUGIN_HOOKS['reports']['olas'], array(
                'front/slas.form.php' => __('SLAs report', 'olas'),
            ));
        }

        if (Session::haveRight("plugin_olas_ola", READ)) {
            olaAddMenu($PLUGIN_HOOKS['reports']['olas'], array(
                'front/olas.form.php' => __('OLAs report', 'olas')
            ));
        }
    }

    $PLUGIN_HOOKS['menu_toadd']['olas'] = array('config' => 'PluginOlasConfig');

}

function olaAddMenu(&$hook, $page)
{
    $hook = is_array($hook) ? array_merge($hook, $page) : $page;
}

function plugin_version_olas()
{

    return array(
        'name' => __('OLAs', 'olas'),
        'version' => '1.1',
        'license' => 'AGPLv3+',
        'author' => 'Cédric Denis, Gilles Dubois',
        'homepage' => 'http://factorfx.com',
        'minGlpiVersion' => '0.85');
}

function plugin_olas_check_prerequisites()
{

    if (version_compare(GLPI_VERSION, '0.90', 'lt') || version_compare(GLPI_VERSION, '0.91', 'ge')) {
        echo "This plugin requires GLPI >= 0.85";
        return false;
    }
    return true;
}

function plugin_olas_check_config()
{
    return true;
}
