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
include('../inc/sla.class.php');

echo "<script src='../js/sorttable.js'></script>";

Session::checkRight("plugin_olas_sla", READ);

Html::header(PluginOlasSla::getTypeName(0), $_SERVER['PHP_SELF'], "tools", "report");

echo "<div class='center'>";
Report::title();
echo "</div>";

PluginOlasCommonForm::getHeader();

$sla = new PluginOlasSla();

if (isset($_GET['submit'])){
    $sla->detailsPerSla(PluginOlasCommonForm::getValue('slas_id'));
    $sla->ticketsDetailsPerSla(PluginOlasCommonForm::getValue('slas_id'));

	if (array_sum(PluginOlasCommonForm::getValue('sections')) > 0) {
		$get_sections = $sla->cleanSectionsArray(PluginOlasCommonForm::getValue('sections'));
    	
    	$percent_per_exceeding = $sla->detailsPerExceedingPercent($get_sections, false);
		$sla->ticketsDetailsExceed($percent_per_exceeding, false);
    	
    	$percent_per_sla = $sla->detailsPerExceedingPercent($get_sections, true);
		$sla->ticketsDetailsExceed($percent_per_sla, true);
    }

	if(PluginOlasCommonForm::getValue('csv')){
		echo "<script type='text/javascript'>document.location.replace('download.php');</script>";
	}
    
}

Html::footer();
