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

class PluginOlasSla extends CommonDBTM
{

    static $rightname = 'plugin_olas_sla';

    static function getTypeName($nb = 0)
    {
        return _n('SLA', 'SLAs', $nb, 'Olas');
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
    
    // Clean get sections and return an array
    public function cleanSectionsArray(array $get_sections) {
    	$size = count($get_sections)+1;
    	for ($i = 2; $i < $size; $i++) {
    		if ($get_sections[$i] == 0) {
    			unset($get_sections[$i]);
    		}
    	}
    	return $get_sections;
    }

    
    /**
     * Create a table with SLA details as number of associted tickets and resolve time allowed in this SLA.
     * 
     * @param unknown $tab_sla
     */
    public function detailsPerSla($tab_sla = array())
    {
    	
        $tab_sla = is_array($tab_sla) ? $tab_sla : (array)$tab_sla;

        echo "<div class='center'>
        	<table class='tab_cadre_fixehov'>
        		   <tr>
                      <th colspan=4>" . __('Details per SLA', 'olas') . "</th>
                      <th><a onclick='reloadWithCsv(\"SLA\");' href='javascript:void(0)'>".__('CSV Export', 'olas')."</a></th>
                  </tr>
        	</table>
			<table class='tab_cadre_fixehov sortable'>
                    <tr>
                        <th><a>" . __('SLA', 'olas') . "</a></th>
                        <th><a>" . __('Tickets Number', 'olas') . "</a></th>
                        <th><a>" . __('% Tickets', 'olas') . "</a></th>
                        <th><a>" . __('SLA Max resolution time', 'olas') . "</a></th>
			    </tr>";

		$my_csv = PluginOlasCommon::openCsvFile("SLA");

        if ($tab_sla[0] == 0) {
            $my_sla = new SLA();

            $array_sla = array();
            foreach ($my_sla->find() as $id => $sla_details) {
                $array_sla[$id] = $sla_details['id'];
            }

            $tab_sla = $array_sla;
        }

        foreach ($tab_sla as $key => $id_sla) {

            global $DB;
            //Retrieve Selected SLA
            $my_sla = PluginOlasCommon::getObjectFromDB("SLA", $id_sla);

            // retrieve get informations and create the query for display
            $query = 'SELECT count(*) FROM `glpi_tickets` WHERE is_deleted = 0';
            $query .= PluginOlasCommon::createQueryForTicket();

            // Retrieve all tickets and all SLA tickets
            $query_total_tickets = $DB->query($query);
            $nb_total_tickets = $DB->fetch_assoc($query_total_tickets);

            $my_tickets = new Ticket();
            $request = "slas_id = " . $id_sla;
            $request .= PluginOlasCommon::createQueryForTicket();

            $sla_related_tickets = PluginOlasCommon::getObjectFromGlpi("Ticket",$request);
            $nb_tickets = count($sla_related_tickets);

            // Get percent of ticket
            $my_ola = new PluginOlasCommon();
            $percent_tickets = $my_ola->pourcentageTickets($nb_tickets, $nb_total_tickets['count(*)']);

            $sla_name = $my_sla->fields['name'];
            $resolve_time = $my_sla->getResolutionTime();
            $resolve_time = PluginOlasCommon::secondsToTime($resolve_time);
            
            // Display SLA's informations
            echo "<tr>
			<td>" . $sla_name . "</td>
			<td><a onclick='toggleTable(\"$sla_name\");' href='javascript:void(0)'>" . $nb_tickets . "</a></td>
			<td>" . $percent_tickets . " % </td>
			<td>" . $resolve_time . "</td>
		  </tr>";

			$sep = $_SESSION['glpicsv_delimiter'];
			$csv_line = "$sla_name$sep$nb_tickets$sep$percent_tickets%$sep$resolve_time$sep\n";
			PluginOlasCommon::addLineToCsv("SLA",$my_csv,$csv_line);

        }

        echo "</table></div>";

    }
    
    
    /**
     * This function is used to create table for selected exceeding percent
     * or for selected exceeding percent depending on the associated SLA
     * 
     * @param unknown $get_sections
     * @param string $sla
     * @return array
     */
    public function detailsPerExceedingPercent(array $get_sections, $sla=false)
    {
    	
    	global $DB;
    	
    	// Get all ticket which are in an SLA.
    	$my_tickets = new Ticket();
    	
    	if (isset($_GET['slas_id']) and $_GET['slas_id'] != 0){
    		$query = "slas_id = ".$_GET['slas_id'];
    	}else{
    		$query = "slas_id != 0";
    	}


        $query .= PluginOlasCommon::createQueryForTicket();
        $my_tickets = PluginOlasCommon::getObjectFromGlpi("ticket", $query);
    	
    	if ($sla == false) {
    		// Create empty array for ticket order
	    	$tri_tickets = array();

    	}
    	else{
    		// Get all SLA (Id and Infos)
            $all_sla = PluginOlasCommon::getObjectFromGlpi("SLA","");
    		
    		// Create empty array for sla
    		$tab_sla = array();
    		
    		// Create Array skeleton for exceeding percent DEPENDING on SLA
    		foreach ($all_sla as $value){
    			$tab_sla[$value['id']] = null;
    		}
    	
    	}
 
    	// Get exceeding percent
    	foreach ($my_tickets as $id => $infos){

            $my_sla = PluginOlasCommon::getObjectFromDB("SLA", $infos['slas_id']);
    		$resolve_time = $my_sla -> getResolutionTime();
    		
    		// If there is no solvedate on the ticket ... creating a "fake" solve date (Actual time)
    		if($infos['solvedate'] == null){
    			$infos['solvedate'] = date('Y-m-d H:i:s');
    		}

            $exec_time = PluginOlasCommon::getExecTime($my_sla->fields['calendars_id'],$infos['date'], $infos['solvedate']);

            // Check if the execution time of the ticket is upper than the resolve time set in the SLA.
            $exceeding_percent = PluginOlasCommon::getExceedingPercent($exec_time, $resolve_time);

    		$infos['exceeding_percent'] = $exceeding_percent;
    		$infos['exec_time'] = $exec_time;

			for($i = 1; $i <= count($get_sections); $i++){
				if ($get_sections[$i] >= $exceeding_percent) {
					if ($get_sections[$i] == 1){
						$value = '0 < X =< '.$get_sections[$i];
					}
					else{
						$value = $get_sections[$i-1]." < X =< ".$get_sections[$i];
					}

					if ($sla == false) {
						$tri_tickets[$value][$infos['id']] = $infos;
					}
					else{
						$tab_sla[$infos['slas_id']][$value][$infos['id']] = $infos;
					}
					break;
				}
			}
    	
    		if ($exceeding_percent > end($get_sections)){
    			if ($sla == false) {
    				$end = end($get_sections);
    				$tri_tickets[">".$end][$infos['id']] = $infos;
    			}
    			else{
    				$end = end($get_sections);
    				$tab_sla[$infos['slas_id']][">".$end][$infos['id']] = $infos;
    			}		
    		}
    		
    	}
    	
    	// retrieve get informations and create the query for display
    	$query = 'SELECT count(*) FROM `glpi_tickets` WHERE is_deleted = 0';
    	$query_total_tickets = $DB->query($query);
    	$nb_total_tickets = $DB->fetch_assoc($query_total_tickets);
    	
    	//Average resolve time
    	$average = 0;

    	if ($sla == false) {
    		// Table Display  (Per exceeding percent)
	    	echo "<div class='center'>
	    		<table class='tab_cadre_fixehov'>
	    		    <tr>
	                   <th colspan=4>" . __('Details per exceeding percent', 'olas') . "</th>
	                   <th><a onclick='reloadWithCsv(\"EXEC\");' href='javascript:void(0)'>".__('CSV Export', 'olas')."</a></th>
	                </tr>
	    		</table>
				<table class='tab_cadre_fixehov sortable'>
	                    <tr>
	                        <th><a>" . __('Tickets Number', 'olas') . "</a></th>
	                        <th><a>" . __('% Tickets', 'olas') . "</a></th>
	                        <th><a>" . __('Average resolve time', 'olas') . "</a></th>
	                        <th><a>" . __('Exceeding Percent', 'olas') . "</a></th>
				    </tr>";

					$my_csv = PluginOlasCommon::openCsvFile("EXEC");
	    	
	    			foreach ($tri_tickets as $excee_percent => $tickets_infos) {
	    		
	    			$average = 0;
	    			
		    		if ($tickets_infos != null) {
		    			foreach ($tickets_infos as $fields) {
			    		    $average = $average + $fields['exec_time'];
			    		}
		    		}
			    	
			    	if ($average > 0) {
			    		$average_resolve = PluginOlasCommon::tempsMoyenResolution($average, count($tickets_infos));
			    	}else{
			    		$average_resolve = "N/A";
			    	}
			    	
		    		echo "<tr>
		    				<td><a onclick='toggleTable(\"$excee_percent\");' href='javascript:void(0)'>".count($tickets_infos)."</a></td>
		    				<td>".PluginOlasCommon::pourcentageTickets(count($tickets_infos), $nb_total_tickets['count(*)'])."</td>
		    				<td>".$average_resolve."</td>
		    				<td>".$excee_percent."</td>
		    				</tr>";

						$sep = $_SESSION['glpicsv_delimiter'];
						$csv_line = count($tickets_infos)."$sep".PluginOlasCommon::pourcentageTickets(count($tickets_infos), $nb_total_tickets['count(*)'])."$sep$average_resolve$sep$excee_percent$sep\n";
						PluginOlasCommon::addLineToCsv("EXEC",$my_csv,$csv_line);
		    		
		    		}
	    
	    	echo "</table></div>";
    	}
    	else{
    		// Table Display  (Per exceeding percent and per sla)
	    	echo "<div class='center'>
	    		<table class='tab_cadre_fixehov'>
	                    <tr>
	                        <th colspan=5>" . __('Details per SLA and exceeding percent', 'olas') . "</th>
	                        <th><a onclick='reloadWithCsv(\"SLAEXEC\");' href='javascript:void(0)'>".__('CSV Export', 'olas')."</a></th>
	                    </tr>
	    		</table>
				<table class='tab_cadre_fixehov sortable'>
	                    <tr>
	                        <th><a>" . __('SLA', 'olas') . "</a></th>
	                        <th><a>" . __('Tickets Number', 'olas') . "</a></th>
	                        <th><a>" . __('% Tickets', 'olas') . "</a></th>
	                        <th><a>" . __('Average resolve time', 'olas') . "</a></th>
	                        <th><a>" . __('Exceeding Percent', 'olas') . "</a></th>
				    </tr>";

				$my_csv = PluginOlasCommon::openCsvFile("SLAEXEC");
	    	 
    			foreach ($tab_sla as $id_sla => $excee_percent) {
    				
 		    		foreach ($excee_percent as $percent => $tickets_infos){
		    			
 		    			$average = 0;
 		    			
		    			if ($tickets_infos != null) {
 	    					foreach ($tickets_infos as $fields) {
 		    		    		$average = $average + $fields['exec_time'];
 		    				}
 	    				}
		    			
 		    			if ($average > 0) {
 		    				$average_resolve = PluginOlasCommon::tempsMoyenResolution($average, count($tickets_infos));
 		    			}else{
 		    				$average_resolve = "N/A";
 		    			}
		    			
 		    			$get_sla = new SLA();
 		    			$get_sla->getFromDB($id_sla);
		    			
 		    			echo "<tr>
 		    				<td>".$get_sla->fields['name']."</td>
 		    				<td><a onclick='toggleTable(\"sla$id_sla$percent\");' href='javascript:void(0)'>".count($tickets_infos)."</a></td>
 		    				<td>".PluginOlasCommon::pourcentageTickets(count($tickets_infos), $nb_total_tickets['count(*)'])."</td>
 		    				<td>".$average_resolve."</td>
 		    				<td>".$percent."</td>
 		    			</tr>";

						$sep = $_SESSION['glpicsv_delimiter'];
						$csv_line = $get_sla->fields['name']."$sep".count($tickets_infos)."$sep".PluginOlasCommon::pourcentageTickets(count($tickets_infos), $nb_total_tickets['count(*)'])."$sep$average_resolve$sep$percent$sep\n";
						PluginOlasCommon::addLineToCsv(PluginOlasCommonForm::getValue('csv'),$my_csv,$csv_line);

 		    		}
		    		
		    	}
	    	
	    	echo "</table></div>";
    	}	

    	if ($sla == false) {
    		return $tri_tickets;
    	}
    	else{
    		return $tab_sla;
    	}
    	
    }
    
   
    /**
     * Depending on the parameters this function will create table of tickets depending on selected exceeding percent 
     * or on exceeding percent depending on associated SLA.
     * 
     * @param array $tickets_infos
     * @param boolean $in_sla
     */
    public function ticketsDetailsExceed(array $tickets_infos, $in_sla = false)
    {
    	
    	if ($in_sla == false) {
    		
    		$status = Ticket::getAllStatusArray();
    		
    		foreach ($tickets_infos as $excee_percent => $tickets) {

                $table_id_clean = PluginOlasCommon::cleanTableId($excee_percent);
    			
    			echo "<div class='center'>
				<table id='$excee_percent' class='tab_cadre_fixehov' style='display:none'>
	                    <tr>
	                        <th colspan=4>" . __('Dynamic tickets list for this exceeding percent range : ', 'olas').$excee_percent."</th>
	                        <th><a onclick='reloadWithCsv(\"$table_id_clean\");' href='javascript:void(0)'>".__('CSV Export', 'olas')."</a></th>
	                        <th><a onclick='hideTable(\"$excee_percent\");' href='javascript:void(0)'>".__('[X] Close','olas')."</a></th>
	                    </tr>
	                    <tr>
	                        <th>" . __('SLA', 'olas') . "</th>
	                        <th>" . __('Ticket ID', 'olas') . "</th>
	                        <th>" . __('Title', 'olas') . "</th>
	                        <th>" . __('Statut', 'olas') . "</th>
	                        <th>" . __('Resolve Time', 'olas') . "</th>
	                        <th>" . __('Exceeding Percent', 'olas') . "</th>
				    </tr>";

				$my_csv = PluginOlasCommon::openCsvFile($table_id_clean);

    			foreach ($tickets as $fields) {

                    $sla = PluginOlasCommon::getObjectFromGlpi("SLA","id = ".$fields['slas_id']);
    				
	    			echo "<tr>
	 		    		<td>".$sla[$fields['slas_id']]['name']."</td>
	 		    		<td><a href='../../../front/ticket.form.php?id=".$fields['id']."' >".$fields['id']."</a></td>
	 		    		<td>".$fields['name']."</td>
	 		    		<td>".$status[$fields['status']]."</td>
	 		    		<td>".PluginOlasCommon::secondsToTime($fields['exec_time'])."</td>
	 		    		<td>".$fields['exceeding_percent']."</td>
	 		    	</tr>";

					$sep = $_SESSION['glpicsv_delimiter'];
					$csv_line = $sla[$fields['slas_id']]['name'].$sep.$fields['id'].$sep.$fields['name'].$sep.$status[$fields['status']].$sep.PluginOlasCommon::secondsToTime($fields['exec_time']).$sep.$fields['exceeding_percent']."\n";
					PluginOlasCommon::addLineToCsv($table_id_clean,$my_csv,$csv_line);
    				 		    	
    			}
    		
    			echo "</table></div>";
    		}
    		
    	}else{
    		
    		$status = Ticket::getAllStatusArray();
    		
    		foreach ($tickets_infos as $id_sla => $excee_percent) {

                $sla = PluginOlasCommon::getObjectFromGlpi("SLA","id = ".$id_sla);
    			$sla_name = $sla[$id_sla]['name'];
    			
    			foreach ($excee_percent as $percent => $id_ticket){

                    $table_id_clean = PluginOlasCommon::cleanTableId("sla$id_sla$percent");
    				
    				echo "<div class='center'>
					<table id=\"sla$id_sla$percent\" class='tab_cadre_fixehov' style='display:none'>
	                    <tr>
	                        <th colspan=4>" . __('Dynamic tickets list for this exceeding percent range : ', 'olas').$percent.__(' in sla : ','olas').$sla_name."</th>
	                        <th><a onclick='reloadWithCsv(\"$table_id_clean\");' href='javascript:void(0)'>".__('CSV Export', 'olas')."</a></th>
	                        <th><a onclick='hideTable(\"sla$id_sla$percent\");' href='javascript:void(0)'>".__('[X] Close','olas')."</a></th>
	                    </tr>
	                    <tr>
	                        <th>" . __('SLA', 'olas') . "</th>
	                        <th>" . __('Ticket ID', 'olas') . "</th>
	                        <th>" . __('Title', 'olas') . "</th>
	                        <th>" . __('Statut', 'olas') . "</th>
	                        <th>" . __('Resolve Time', 'olas') . "</th>
	                        <th>" . __('Exceeding Percent', 'olas') . "</th>
				    </tr>";

					$my_csv = PluginOlasCommon::openCsvFile($table_id_clean);
    				
    				foreach ($id_ticket as $fields){
    					 
    					echo "<tr>
	 		    		<td>".$sla_name."</td>
	 		    		<td><a href='../../../front/ticket.form.php?id=".$fields['id']."' >".$fields['id']."</a></td>
	 		    		<td>".$fields['name']."</td>
	 		    		<td>".$status[$fields['status']]."</td>
	 		    		<td>".PluginOlasCommon::secondsToTime($fields['exec_time'])."</td>
	 		    		<td>".$fields['exceeding_percent']."</td>
	 		    		</tr>";

						$sep = $_SESSION['glpicsv_delimiter'];
						$csv_line = $sla_name.$sep.$fields['id'].$sep.$fields['name'].$sep.$status[$fields['status']].$sep.PluginOlasCommon::secondsToTime($fields['exec_time']).$sep.$fields['exceeding_percent']."\n";
						PluginOlasCommon::addLineToCsv($table_id_clean,$my_csv,$csv_line);
    				
    				}
    				
    				echo "</table></div>";
    				
    			}
    			
    		}
    		
    	}
    	
    
    }
    
    /**
     * This function is used to create the dynamic ticket table for the "Details per sla" table
     * 
     * @param number $sla_id
     */
    public function ticketsDetailsPerSla($sla_id = 0)
    {
    	 
    	if ($sla_id == 0) {
            $all_sla = PluginOlasCommon::getObjectFromGlpi("SLA","");
    	}else{
            $all_sla = PluginOlasCommon::getObjectFromGlpi("SLA","id = ".$sla_id);
    	}
    	
    	$status = Ticket::getAllStatusArray();
    	 
    	foreach ($all_sla as $id_sla => $infos_sla) {

            $sla_tickets = PluginOlasCommon::getObjectFromGlpi("Ticket","slas_id = ".$id_sla);
    		
    		$sla_name = $all_sla[$id_sla]['name'];
    		
    		echo "<div class='center'>
    		<table id=\"$sla_name\" class='tab_cadre_fixehov' style='display:none'>
    					<tr>
    						<th colspan=4>".__('Dynamic tickets list in sla : ', 'olas').$sla_name."</th>
    						<th><a onclick='reloadWithCsv(\"$sla_name\");' href='javascript:void(0)'>".__('CSV Export', 'olas')."</a></th>
    						<th><a onclick='hideTable(\"$sla_name\");' href='javascript:void(0)'>".__('[X] Close','olas')."</a></th>
	                    </tr>
	                    <tr>
	                        <th>" . __('SLA', 'olas') . "</th>
	                        <th>" . __('Ticket ID', 'olas') . "</th>
	                        <th>" . __('Title', 'olas') . "</th>
	                        <th>" . __('Statut', 'olas') . "</th>
	                        <th>" . __('Resolve Time', 'olas') . "</th>
	                        <th>" . __('Exceeding Percent', 'olas') . "</th>
				    </tr>";

			$my_csv = PluginOlasCommon::openCsvFile($sla_name);

    		foreach ($sla_tickets as $id_tickets => $fields){
				
    			// If there is no solvedate on the ticket ... creating a "fake" solve date (Actual time)
    			if($fields['solvedate'] == null){
    				$fields['solvedate'] = date('Y-m-d H:i:s');
    			}

                $exec_time = PluginOlasCommon::getExecTime($all_sla[$id_sla]['calendars_id'],$fields['date'], $fields['solvedate']);
    			
    			// Get resolve time
    			$current_sla = new SLA();
    			$current_sla -> getFromDB($id_sla);
    			$resolve_time = $current_sla -> getResolutionTime();
    			
    			// Check if the execution time of the ticket is upper than the resolve time set in the SLA.
    			if ($exec_time > $resolve_time){
    				$exceeding_percent =  round(($exec_time-$resolve_time)/$resolve_time*100, 2);
    			}else{
    				$exceeding_percent = 0;
    			}
    			
    			echo "<tr>
	 		    		<td>".$sla_name."</td>
	 		    		<td><a href='../../../front/ticket.form.php?id=".$fields['id']."' >".$fields['id']."</a></td>
	 		    		<td>".$fields['name']."</td>
	 		    		<td>".$status[$fields['status']]."</td>
	 		    		<td>".PluginOlasCommon::secondsToTime($exec_time)."</td>
	 		    		<td>".$exceeding_percent."</td>
	 		    	</tr>";

				$sep = $_SESSION['glpicsv_delimiter'];
				$csv_line = $sla_name.$sep.$fields['id'].$sep.$fields['name'].$sep.$status[$fields['status']].$sep.PluginOlasCommon::secondsToTime($fields['exec_time']).$sep.$fields['exceeding_percent'];
				PluginOlasCommon::addLineToCsv($sla_name,$my_csv,$csv_line);
    			
    		}
    		
    		echo "</table></div>";
    		
    	}
    
    }    
    
}