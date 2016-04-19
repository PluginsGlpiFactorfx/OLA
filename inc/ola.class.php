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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginOlasOla extends CommonDBTM
{

    static $rightname = 'plugin_olas_ola';

    static function getTypeName($nb = 0)
    {
        return _n('OLA', 'OLAs', $nb, 'Olas');
    }

    /**
     * Get rights for an item _ may be overload by object
     *
     * @since version 0.85
     *
     * @param $interface   string   (default 'central')
     *
     * @return array of rights to display
     **/
    function getRights($interface = 'central')
    {
        return array(READ => __s('Read'));
    }

    public function detailsPerGroup($tab_group = array())
    {
        $tab_group = is_array($tab_group) ? $tab_group : (array)$tab_group;

        echo "<div class='center'>
        	<table class='tab_cadre_fixehov' id='OLAD'>
        		   <tr>
                      <th colspan=4>" . __('Details per OLA', 'olas') . "</th>
                      <th><a onclick='reloadWithCsv(\"OLAD\");' href='javascript:void(0)'>".__('CSV Export', 'olas')."</a></th>
                  </tr>
        	</table>
			<table class='tab_cadre_fixehov sortable'>
                    <tr>
                        <th><a>" . __('Support Center', 'olas') . "</a></th>
                        <th><a>" . __('SLA', 'olas') . "</a></th>
                        <th><a>" . __('Intervention Nb', 'olas') . "</a></th>
                        <th><a>" . __('% Intervention', 'olas') . "</a></th>
                        <th><a>" . __('Max time Ola', 'olas') . "</a></th>
			    </tr>";

        $my_csv = PluginOlasCommon::openCsvFile('OLAD');
        $array_group = PluginOlasCommon::getobjectArray('Group', PluginOlasCommonForm::getValue('group_id'), true, true);

        // Check if SLA filter = all
        if(PluginOlasCommonForm::getValue('slas_id') == 0){
            $selected_sla = PluginOlasCommon::getObjectFromGlpi("SLA","");
        }else{
            $id_sla = PluginOlasCommonForm::getValue('slas_id');
            $selected_sla = PluginOlasCommon::getObjectFromGlpi("SLA","id = $id_sla");
        }

        //Tickets Tab
        $tickets_array = array();

        foreach ($array_group as $id_group => $group_name) {

            global $DB;

            // Retrieve all tickets in a group.
            $group_tickets = PluginOlasCommon::getObjectFromGlpi("Group_Ticket","groups_id = $id_group");

            $list_ticket = array();
            // Clean array with ticket list ID for the selected group.
            foreach ($group_tickets as $id_ent => $fields) {
                array_push($list_ticket, $fields["tickets_id"]);
            }
            // Create string from array for query ticket from group AND sla :)
            $in_string = implode(",",$list_ticket);

            // Get all informations about intervations for this support center
            $detail_intervention_total = PluginOlasCommon::getObjectFromGlpi("Log","itemtype = 'Ticket' and new_value = '$group_name ($id_group)' and items_id IN ($in_string)");
            $nb_intervention_total = count($detail_intervention_total);

            foreach ($selected_sla as $id_sla => $fields){
                // Retrieve ticket which are in the group ($in_string) and from current sla in loop
                $query = "slas_id = $id_sla and id IN ($in_string)";
                $query .= PluginOlasCommon::createQueryForTicket(true);
                $tickets = PluginOlasCommon::getObjectFromGlpi("Ticket",$query);

                // Display something if at least 1 ticket
                if(!empty($tickets)){

                    // Get OLA From SLA if there is something to print
                    $my_ola = PluginOlasCommon::getObjectFromGlpi("PluginOlasConfig","slas_id = $id_sla");
                    foreach ($my_ola as $id_ola => $fields_ola) {
                        $time_ola = $fields_ola['processing_time']." ".$fields_ola['definition_time'];
                        $id_ola = $fields_ola['id'];
                    }

                    // Get intervention logs for retrieved ticket
                    $list_retrieved_ticket = array();
                    foreach ($tickets as $id_ticket => $fields_ticket) {
                        array_push($list_retrieved_ticket, $fields_ticket["id"]);
                    }
                    $list_retrieved_ticket = implode(",",$list_retrieved_ticket);

                    // Retrieve log for intervention number
                    $detail_intervention = PluginOlasCommon::getObjectFromGlpi("Log","itemtype = 'Ticket' and new_value = '$group_name ($id_group)' and items_id IN ($list_retrieved_ticket)");
                    $nb_intervention = count($detail_intervention);

                    // Get the percent number
                    $percent_intervention = PluginOlasCommon::pourcentageTickets($nb_intervention, $nb_intervention_total);

                    $sla_name = $fields['name'];

                    // Display OLA details' informations
                    echo "<tr>
                        <td>" . $group_name . "</td>
                        <td>" . $sla_name . "</td>
                        <td><a onclick='toggleTable(\"$group_name.$sla_name\");' href='javascript:void(0)'>" . $nb_intervention . "</a></td>
                        <td>" . $percent_intervention . " % </td>
                        <td>" . $time_ola . "</td>
                      </tr>";

                    // Retrieve tickets in an Array for later use.
                    foreach ($tickets as $id_ticket => $fields_ticket) {
                        $tickets_array[$id_group][$sla_name][$id_ticket] = $fields_ticket;
                        $tickets_array[$id_group][$sla_name][$id_ticket]['id_ola'] = $id_ola;
                    }

                    $sep = $_SESSION['glpicsv_delimiter'];
                    $csv_line = "$group_name$sep$sla_name$sep$nb_intervention$sep$percent_intervention%$sep$time_ola$sep\n";
                    PluginOlasCommon::addLineToCsv("OLAD",$my_csv,$csv_line);

                }

            }

        }

        echo "</table></div>";

        return $tickets_array;
    }

    /**
     * Create ticket tab for details per OLA from a ticket array
     *
     * @param array $tab_ticket
     */
    public function ticketsDetailsPerOla(array $tab_ticket)
    {
        $t = "N/A";

        $status = Ticket::getAllStatusArray();

        foreach ($tab_ticket as $id_group => $tab_sla) {

            $group = PluginOlasCommon::getObjectFromDB("Group",$id_group);
            $group_name = $group->fields['name'];

            foreach ($tab_sla as $sla_name => $tickets) {

                echo "<div class='center'>
			    <table id=\"$group_name.$sla_name\" class='tab_cadre_fixehov' style='display:none'>
			        <tr>
                      <th colspan=6>" . __('List of dynamic intervention for ', 'olas'). $group_name .__(' and ','olas').$sla_name. "</th>
                      <th><a onclick='reloadWithCsv(\"$group_name.$sla_name\");' href='javascript:void(0)'>".__('CSV Export', 'olas')."</a></th>
                      <th><a onclick='hideTable(\"$group_name.$sla_name\");' href='javascript:void(0)'>" . __('[X] Close', 'olas') . "</a></th>
                  </tr>
                    <tr>
                        <th><a>" . __('Support Center', 'olas') . "</a></th>
                        <th><a>" . __('SLA', 'olas') . "</a></th>
                        <th><a>" . __('Problems ID', 'olas') . "</a></th>
                        <th><a>" . __('Ticket ID', 'olas') . "</a></th>
                        <th><a>" . __('Title', 'olas') . "</a></th>
                        <th><a>" . __('Status', 'olas') . "</a></th>
                        <th><a>" . __('Resolve Time', 'olas') . "</a></th>
                        <th><a>" . __('Exceeding percent', 'olas') . "</a></th>
			    </tr>";

                $my_csv = PluginOlasCommon::openCsvFile($group_name.".".$sla_name);

                foreach ($tickets as $id_ticket => $fields) {


                    // If no solvedate create a fake one.
                    if($fields['solvedate'] == null){
                        $fields['solvedate'] = date('Y-m-d H:i:s');
                    }

                    $ola = PluginOlasCommon::getObjectFromDB("PluginOlasConfig",$fields['id_ola']);
                    $resolve_time = self::getOlasResolveTime($ola->fields['processing_time'],$ola->fields['definition_time']);

                    $exec_time = PluginOlasCommon::getExecTime($ola->fields['calendars_id'],$fields['date'],$fields['solvedate']);

                    $exceeding_percent = PluginOlasCommon::getExceedingPercent($exec_time,$resolve_time);

                    $time = PluginOlasCommon::secondsToTime($exec_time);

                    $t = PluginOlasCommon::getAssocProblem($fields['id']);

                    echo "<tr>
                        <td>" . $group_name . "</td>
                        <td><a>" . $sla_name . "</a></td>
                        <td>" . $t . "</td>
                        <td>" . $fields['id'] . "</td>
                        <td>" . $fields['name'] . "</td>
                        <td>" . $status[$fields['status']]. "</td>
                        <td>" . $time . "</td>
                        <td>" . $exceeding_percent . " %</td>
                      </tr>";

                    $sep = $_SESSION['glpicsv_delimiter'];
                    $csv_line = "$group_name$sep$sla_name$sep$t$sep".$fields['id']."$sep".$fields['name']."$sep".$status[$fields['status']]."$sep$time$sep$exceeding_percent$sep\n";
                    PluginOlasCommon::addLineToCsv($group_name.".".$sla_name,$my_csv,$csv_line);

                }


                echo "</table></div>";

            }

        }

    }

    /**
     * This function create table with stats if exceeding percent filter has been used in the OLA Report.
     *
     * @param array $sections : exceeding percent params.
     * @param boolean $in_sla : will determine if we use sla or not
     * @return array => ticket array for dynamic ticket list
     */
    public function detailsPerExceedingPercent($sections, $in_sla){

        $array_group = PluginOlasCommon::getobjectArray('Group', PluginOlasCommonForm::getValue('group_id'), true, true);
        $array_tickets = array();

        foreach ($array_group as $group_id => $group_name ) {

            //Retrieve Ticket groups
            $group_tickets = PluginOlasCommon::getObjectFromGlpi("Group_Ticket","groups_id = $group_id");

            // Create list of ticket id from result
            $ticket_list = array();
            foreach ($group_tickets as $id_ent => $fields_groups_tickets) {
                array_push($ticket_list, $fields_groups_tickets['tickets_id']);
            }

            // Retrieve tickets and get all needed informations for ola and sla
            foreach ($ticket_list as $index => $id_ticket) {

                $query = "id = $id_ticket";
                $query .= PluginOlasCommon::createQueryForTicket(true);
                $my_ticket = PluginOlasCommon::getObjectFromGlpi("Ticket",$query);

                if(!empty($my_ticket)){
                    // If no solvedate create a fake one.
                    if($my_ticket[$id_ticket]['solvedate'] == null){
                        $my_ticket[$id_ticket]['solvedate'] = date('Y-m-d H:i:s');
                    }

                    // Retrieve OLA associated to this SLA and associate it to the ticket.
                    $my_ola = PluginOlasCommon::getObjectFromGlpi("PluginOlasConfig","slas_id = ".$my_ticket[$id_ticket]['slas_id']);
                    foreach ($my_ola as $id_ola => $fields_ola) {
                        $my_ticket[$id_ticket]['olas_id'] = $id_ola;
                        $my_ticket[$id_ticket]['olas_calendars_id'] = $fields_ola['calendars_id'];
                        $my_ticket[$id_ticket]['olas_processing_time'] = $fields_ola['processing_time'];
                        $my_ticket[$id_ticket]['olas_definition_time'] = $fields_ola['definition_time'];
                    }

                    if ($in_sla){
                        $sla = PluginOlasCommon::getObjectFromDB("SLA",$my_ticket[$id_ticket]['slas_id']);
                        $sla_name = $sla->fields['name'];
                    }

                    $resolve_time = self::getOlasResolveTime($my_ticket[$id_ticket]['olas_processing_time'],$my_ticket[$id_ticket]['olas_definition_time']);

                    $exec_time = PluginOlasCommon::getExecTime($my_ticket[$id_ticket]['olas_calendars_id'],$my_ticket[$id_ticket]['date'],$my_ticket[$id_ticket]['solvedate']);

                    // Check if the execution time of the ticket is upper than the resolve time set in the OLA.
                    $exceeding_percent = PluginOlasCommon::getExceedingPercent($exec_time,$resolve_time);

                    $my_ticket[$id_ticket]['exceeding_percent'] = $exceeding_percent;
                    $my_ticket[$id_ticket]['exec_time'] = $exec_time;

                    for($i = 1; $i <= count($sections); $i++){
                        if ($sections[$i] >= $exceeding_percent) {
                            if ($sections[i] == 1){
                                $value = '0 < X =< '.$sections[$i];
                            }
                            else{
                                $value = $sections[$i-1]." < X =< ".$sections[$i];
                            }

                            if ($in_sla == false) {
                                $array_tickets[$group_id][$value][$id_ticket] = $my_ticket[$id_ticket];
                            }
                            else{
                                $array_tickets[$group_id][$sla_name][$value][$id_ticket] = $my_ticket[$id_ticket];
                            }
                            break;
                        }
                    }

                    if ($exceeding_percent > end($sections)){
                        if ($in_sla == false) {
                            $end = end($sections);
                            $array_tickets[$group_id]["> ".$end][$id_ticket] = $my_ticket[$id_ticket];
                        }
                        else{
                            $end = end($sections);
                            $array_tickets[$group_id][$sla_name]["> ".$end][$id_ticket] = $my_ticket[$id_ticket];
                        }
                    }
                }

            }

        }

        // display depending on function parameter
        if (!$in_sla){

            echo "<div class='center'>
        	<table class='tab_cadre_fixehov' id='EXECSUPCENTER'>
        		   <tr>
                      <th colspan=4>" . __('Details per support center and exceeding percent', 'olas') . "</th>
                      <th><a onclick='reloadWithCsv(\"EXECSUPCENTER\");' href='javascript:void(0)'>".__('CSV Export', 'olas')."</a></th>
                  </tr>
        	</table>
			<table class='tab_cadre_fixehov sortable'>
                    <tr>
                        <th><a>" . __('Support Center', 'olas') . "</a></th>
                        <th><a>" . __('Intervention Nb', 'olas') . "</a></th>
                        <th><a>" . __('% Intervention', 'olas') . "</a></th>
                        <th><a>" . __('Exceeding percent', 'olas') . "</a></th>
			    </tr>";

            $my_csv = PluginOlasCommon::openCsvFile('EXECSUPCENTER');

            foreach ($array_tickets as $id_group => $array_percent) {

                // Get group
                $my_group = PluginOlasCommon::getObjectFromDB("Group",$id_group);
                $group_name = $my_group->fields['name'];

                // Get total intervention number for this group
                $my_log_total = PluginOlasCommon::getObjectFromGlpi("Log", "itemtype = 'Ticket' and new_value = '$group_name ($id_group)'");
                $nb_intervention_total = count($my_log_total);

                foreach ($array_percent as $exceeding_percent => $tickets) {

                    $in_string = array();
                    foreach ($tickets as $id_ticket => $fields_tickets) {
                        array_push($in_string, $id_ticket);
                    }
                    $in_string = implode(",",$in_string);

                    // Get intervention number
                    $my_log = PluginOlasCommon::getObjectFromGlpi("Log","itemtype = 'Ticket' and new_value = '$group_name ($id_group)' and items_id IN ($in_string)");
                    $nb_intervention = count($my_log);

                    $percent_intervention = PluginOlasCommon::pourcentageTickets($nb_intervention, $nb_intervention_total);

                    // Display OLA exceeding percent informations
                    echo "<tr>
                        <td>" . $group_name . "</td>
                        <td><a onclick='toggleTable(\"$group_name.$exceeding_percent\");' href='javascript:void(0)'>" . $nb_intervention . "</a></td>
                        <td>" . $percent_intervention . " % </td>
                        <td>" . $exceeding_percent . "</td>
                      </tr>";

                    $sep = $_SESSION['glpicsv_delimiter'];
                    $csv_line = "$group_name$sep$nb_intervention$sep$percent_intervention%$sep$exceeding_percent$sep\n";
                    PluginOlasCommon::addLineToCsv("EXECSUPCENTER",$my_csv,$csv_line);

                }

            }

        }else {

            echo "<div class='center'>
        	<table class='tab_cadre_fixehov' id='OLAEXECPERCENT'>
        		   <tr>
                      <th colspan=4>" . __('Details per OLA and exceeding percent', 'olas') . "</th>
                      <th><a onclick='reloadWithCsv(\"OLAEXECPERCENT\");' href='javascript:void(0)'>".__('CSV Export', 'olas')."</a></th>
                  </tr>
        	</table>
			<table class='tab_cadre_fixehov sortable'>
                    <tr>
                        <th><a>" . __('Support Center', 'olas') . "</a></th>
                        <th><a>" . __('SLA', 'olas') . "</a></th>
                        <th><a>" . __('Intervention Nb', 'olas') . "</a></th>
                        <th><a>" . __('% Intervention', 'olas') . "</a></th>
                        <th><a>" . __('Exceeding percent', 'olas') . "</a></th>
			    </tr>";

            $my_csv = PluginOlasCommon::openCsvFile('OLAEXECPERCENT');

            foreach ($array_tickets as $id_group => $in_sla_name) {

                // Get group
                $my_group = PluginOlasCommon::getObjectFromDB("Group", $id_group);
                $group_name = $my_group->fields['name'];

                // Get total intervention number for this group
                $my_log_total = PluginOlasCommon::getObjectFromGlpi("Log","itemtype = 'Ticket' and new_value = '$group_name ($id_group)'");
                $nb_intervention_total = count($my_log_total);

                foreach ($in_sla_name as $sla_name => $tickets) {

                    foreach ($tickets as $exceeding_percent => $fields_tickets) {

                        $in_string = array();
                        foreach ($fields_tickets as $id => $fields) {
                            array_push($in_string, $id);
                        }
                        $in_string = implode(",",$in_string);

                        // Get intervention number
                        $my_log = PluginOlasCommon::getObjectFromGlpi("Log","itemtype = 'Ticket' and new_value = '$group_name ($id_group)' and items_id IN ($in_string)");
                        $nb_intervention = count($my_log);

                        $percent_intervention = PluginOlasCommon::pourcentageTickets($nb_intervention, $nb_intervention_total);

                        // Display OLA exceeding percent informations
                        echo "<tr>
                        <td>" . $group_name . "</td>
                        <td>" . $sla_name . "</td>
                        <td><a onclick='toggleTable(\"OLA$group_name.$sla_name\");' href='javascript:void(0)'>" . $nb_intervention . "</a></td>
                        <td>" . $percent_intervention . " % </td>
                        <td>" . $exceeding_percent . "</td>
                        </tr>";

                        $sep = $_SESSION['glpicsv_delimiter'];
                        $csv_line = "$group_name$sep$sla_name$sep$nb_intervention$sep$percent_intervention%$sep$exceeding_percent$sep\n";
                        PluginOlasCommon::addLineToCsv("OLAEXECPERCENT",$my_csv,$csv_line);

                    }

                }

            }

            echo "</table></div>";

        }
        return $array_tickets;
    }

    /**
     * Create ticket tab for details per OLA or support center and exceeding percent from a ticket array
     *
     * @param array $tab_ticket
     */
    public function ticketsDetailsPerExceedingPercent(array $tab_ticket, $by_ola = true)
    {

        $status = Ticket::getAllStatusArray();

        if($by_ola){

            foreach ($tab_ticket as $id_group => $tab_sla) {

                $group = PluginOlasCommon::getObjectFromDB("Group", $id_group);
                $group_name = $group->fields['name'];

                foreach ($tab_sla as $sla_name => $tickets) {

                    $table_id_clean = PluginOlasCommon::cleanTableId("OLA$group_name$sla_name");

                    echo "<div class='center'>
			    <table id=\"OLA$group_name.$sla_name\" class='tab_cadre_fixehov' style='display:none'>
			        <tr>
                      <th colspan=6>" . __('Intervention list per group, sla and exceeding percent', 'olas') . "</th>
                      <th><a onclick='reloadWithCsv(\"$table_id_clean\");' href='javascript:void(0)'>".__('CSV Export', 'olas')."</a></th>
                      <th><a onclick='hideTable(\"OLA$group_name.$sla_name\");' href='javascript:void(0)'>" . __('[X] Close', 'olas') . "</a></th>
                  </tr>
                    <tr>
                        <th><a>" . __('Support Center', 'olas') . "</a></th>
                        <th><a>" . __('SLA', 'olas') . "</a></th>
                        <th><a>" . __('Problems ID', 'olas') . "</a></th>
                        <th><a>" . __('Ticket ID', 'olas') . "</a></th>
                        <th><a>" . __('Title', 'olas') . "</a></th>
                        <th><a>" . __('Status', 'olas') . "</a></th>
                        <th><a>" . __('Resolve Time', 'olas') . "</a></th>
                        <th><a>" . __('Exceeding percent', 'olas') . "</a></th>
			    </tr>";

                    $my_csv = PluginOlasCommon::openCsvFile($table_id_clean);

                    foreach ($tickets as $tab => $fields_tab) {

                        foreach ($fields_tab as $id => $fields) {
                            // If no solvedate create a fake one.
                            if($fields['solvedate'] == null){
                                $fields['solvedate'] = date('Y-m-d H:i:s');
                            }

                            $ola = PluginOlasCommon::getObjectFromDB("PluginOlasConfig", $fields['olas_id']);

                            $resolve_time = self::getOlasResolveTime($ola->fields['processing_time'],$ola->fields['definition_time']);

                            $exec_time = PluginOlasCommon::getExecTime($ola->fields['calendars_id'],$fields['date'],$fields['solvedate']);

                            $exceeding_percent = PluginOlasCommon::getExceedingPercent($exec_time, $resolve_time);

                            $time = PluginOlasCommon::secondsToTime($exec_time);

                            $t = PluginOlasCommon::getAssocProblem($fields['id']);

                            echo "<tr>
                        <td>" . $group_name . "</td>
                        <td>" . $sla_name . "</td>
                        <td>" . $t . "</td>
                        <td>" . $fields['id'] . "</td>
                        <td>" . $fields['name'] . "</td>
                        <td>" . $status[$fields['status']]. "</td>
                        <td>" . $time . "</td>
                        <td>" . $exceeding_percent . " %</td>
                      </tr>";

                            $sep = $_SESSION['glpicsv_delimiter'];
                            $csv_line = "$group_name$sep$sla_name$sep$t$sep".$fields['id']."$sep".$fields['name']."$sep".$status[$fields['status']]."$sep$time$sep$exceeding_percent$sep\n";
                           PluginOlasCommon::addLineToCsv($table_id_clean,$my_csv,$csv_line);

                        }

                    }

                    echo "</table></div>";

                }

            }

        }
        else{
            foreach ($tab_ticket as $id_group => $array_percent) {

                $group = PluginOlasCommon::getObjectFromDB("Group",$id_group);
                $group_name = $group->fields['name'];

                foreach ($array_percent as $exceeding_percent => $tickets) {

                    $table_id_clean = PluginOlasCommon::cleanTableId($group_name.$exceeding_percent);

                    echo "<div class='center' >
			    <table id=\"$group_name.$exceeding_percent\" class='tab_cadre_fixehov' style='display:none'>

                          <th colspan=6>" . __('Intervention list per group and exceeding percent', 'olas') . "</th>
                          <th><a onclick='reloadWithCsv(\"$table_id_clean\");' href='javascript:void(0)'>".__('CSV Export', 'olas')."</a></th>
                          <th><a onclick='hideTable(\"$group_name.$exceeding_percent\");' href='javascript:void(0)'>" . __('[X] Close', 'olas') . "</a></th>

                    <tr>
                        <th><a>" . __('Support Center', 'olas') . "</a></th>
                        <th><a>" . __('SLA', 'olas') . "</a></th>
                        <th><a>" . __('Problems ID', 'olas') . "</a></th>
                        <th><a>" . __('Ticket ID', 'olas') . "</a></th>
                        <th><a>" . __('Title', 'olas') . "</a></th>
                        <th><a>" . __('Status', 'olas') . "</a></th>
                        <th><a>" . __('Resolve Time', 'olas') . "</a></th>
                        <th><a>" . __('Exceeding percent', 'olas') . "</a></th>
			    </tr>";

                    $my_csv = PluginOlasCommon::openCsvFile($table_id_clean);

                    foreach ($tickets as $id => $fields) {

                        $time = PluginOlasCommon::secondsToTime($fields['exec_time']);

                        $sla = PluginOlasCommon::getObjectFromDB("SLA",$fields['slas_id']);
                        $sla_name = $sla->fields['name'];

                        $t = PluginOlasCommon::getAssocProblem($fields['id']);

                        echo "<tr>
                        <td>" . $group_name . "</td>
                        <td>" . $sla_name . "</td>
                        <td>" . $t . "</td>
                        <td>" . $fields['id'] . "</td>
                        <td>" . $fields['name'] . "</td>
                        <td>" . $status[$fields['status']]. "</td>
                        <td>" . $time . "</td>
                        <td>" . $fields['exceeding_percent'] . " %</td>
                      </tr>";

                        $sep = $_SESSION['glpicsv_delimiter'];
                        $csv_line = "$group_name$sep$sla_name$sep$t$sep".$fields['id']."$sep".$fields['name']."$sep".$status[$fields['status']]."$sep$time$sep$exceeding_percent$sep\n";
                        PluginOlasCommon::addLineToCsv($table_id_clean,$my_csv,$csv_line);

                    }
                    echo "</table></div>";

                }

            }
        }
    }

    /**
     * @param int $process_time : the amout of def time (OLA object)
     * @param string $def_time : definiton time (Hour / minute / day)
     * @return int : max allowed time by the ola in seconds
     */
    public function getOlasResolveTime($process_time, $def_time){
        if ($def_time == "day"){
            return $process_time*86400;
        }
        elseif ($def_time == "hour"){
            return $process_time*3600;
        }
        elseif ($def_time == "minute"){
            return $process_time*60;
        }
    }


}