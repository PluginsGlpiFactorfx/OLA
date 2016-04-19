<?php
/**
 * @package    olas
 * @author     Cédric Denis, Gilles Dubois
 * @copyright  Copyright (c) 2010-2015 FactorFX, Linagora
 * @license    AGPL License 3.0 or (at your option) any later version
 * http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link       https://www.factorfx.com
 * @link       http://www.linagora.com
 * @since      2015
 *
 * --------------------------------------------------------------------------
 */

include('../../../inc/includes.php');
include('../inc/common.class.php');
include('../inc/commonForm.class.php');
include('../inc/ola.class.php');

echo "<script src='../js/sorttable.js'></script>";

PluginOlasCommonForm::setOla(true);

Session::checkRight("plugin_olas_ola", READ);

Html::header(PluginOlasOla::getTypeName(2), $_SERVER['PHP_SELF'], "tools", "report");

echo "<div class='center'>";
Report::title();
echo "</div>";

PluginOlasCommonForm::getHeader();

$ola = new PluginOlasOla();

PluginOlasCommon::checkEntityForReport();

if (isset($_GET['submit'])) {
    $ticket_array = $ola->detailsPerGroup(PluginOlasCommonForm::getValue('groups_id'));
    $ola->ticketsDetailsPerOla($ticket_array);

    if (array_sum(PluginOlasCommonForm::getValue('sections')) > 0 ) {
        // Retrieve a clean array of exceeding percent sections
        $sla = new PluginOlasSla();
        $get_sections = $sla->cleanSectionsArray(PluginOlasCommonForm::getValue('sections'));

        $exceeding_percent_per_group = $ola->detailsPerExceedingPercent($get_sections,  false);
        $ola->ticketsDetailsPerExceedingPercent($exceeding_percent_per_group, false);

        $percent_per_sla = $ola->detailsPerExceedingPercent($get_sections, true);
        $ola->ticketsDetailsPerExceedingPercent($percent_per_sla, true);
    }

    if(PluginOlasCommonForm::getValue('csv')){
        echo "<script type='text/javascript'>document.location.replace('download.php');</script>";
    }

}

Html::footer();
