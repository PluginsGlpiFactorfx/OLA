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

include('../../../inc/includes.php');

Session::checkRight("plugin_olas_config", READ);

Html::header(PluginOlasConfig::getTypeName(2), $_SERVER['PHP_SELF'], "config", "PluginOlasConfig");

Search::show('PluginOlasConfig');

Html::footer();