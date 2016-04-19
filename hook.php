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


function plugin_olas_install()
{
    // No autoload when plugin is not activated
    olasAutoInclude();

    $migration = new Migration('1.0');

    $plugin_config = new PluginOlasConfig();
    $plugin_config->install($migration);
    PluginOlasProfile::install($migration);

    $migration->executeMigration();

    return true;
}


function plugin_olas_uninstall()
{
    // No autoload when plugin is not activated
    olasAutoInclude();

    $plugin_config = new PluginOlasConfig();
    $plugin_config->uninstall();
    PluginOlasProfile::uninstall();

    return true;
}

function olasAutoInclude()
{
    foreach (glob(GLPI_ROOT . '/plugins/olas/inc/*.php') as $file) {
        include_once($file);
    }
}