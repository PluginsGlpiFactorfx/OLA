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

include("../../../inc/includes.php");

Session::checkRight("plugin_olas_config", READ);

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$plugin_ola_config = new PluginOlasConfig();

if (isset($_POST["add"])) {
    $plugin_ola_config->check(-1, CREATE);

    if ($newID = $plugin_ola_config->add($_POST)) {
        Event::log($newID, "PluginOlasConfig", 4, "setup", sprintf(__s('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($plugin_ola_config->getFormURL() . "?id=" . $newID);
        }
    }
    Html::redirect($CFG_GLPI["root_doc"] . "/plugins/olas/front/config.php");

} else if (isset($_POST["purge"])) {
    $plugin_ola_config->check($_POST["id"], PURGE);
    $plugin_ola_config->delete($_POST, 1);

    Event::log($_POST["id"], "PluginOlasConfig", 4, "setup", sprintf(__s('%s purges an item'), $_SESSION["glpiname"]));
    $plugin_ola_config->redirectToList();

} else if (isset($_POST["update"])) {
    $plugin_ola_config->check($_POST["id"], UPDATE);
    $plugin_ola_config->update($_POST);

    Event::log($_POST["id"], "PluginOlasConfig", 4, "setup", sprintf(__s('%s updates an item'), $_SESSION["glpiname"]));
    Html::back();

} else {
    Html::header(PluginOlasConfig::getTypeName(2), $_SERVER['PHP_SELF'], "config", "PluginOlasConfig");

    $plugin_ola_config->display(array('id' => $_GET["id"]));
    Html::footer();
}