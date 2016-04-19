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
class PluginOlasCommon
{

    static function getobjectArray($object, $values = array(), $check_entities = false, $recursive = false)
	{
		$values = is_array($values) ? $values : (array)$values;
		if (class_exists($object)) {
			$ret = $ids = array();
			$obj = new $object();
			if (isset($values[0]) && $values[0] == 0) {
				foreach ($obj->find() as $res) {
                    $obj->getFromDB($res['id']);
                    //$_obj = new $object();
                    //if ($check_entities === true && $obj->checkEntity($recursive)) {
                    $ret[$res['id']] = $res['name'];
                    //}
				}
			} else {
				foreach ($values as $id) {
                    $obj->getFromDB($id);
                    //if ($check_entities === true && $obj->checkEntity($recursive)) {
                    $ret[$obj->fields['id']] = $obj->fields['name'];
                    //}
				}
			}
			return $ret;
		}
		return false;
	}

	static function getAllObjectIds($object)
	{
		if (class_exists($object)) {
			$ids = array();
			$obj = new $object();
			foreach ($obj->find() as $res) {
				$ids[] = $res['id'];
			}
			return $ids;
		}
		return false;
	}

    /**
     * Create a query for glpi sql base depending on user's rapport filters
     *
     * @return string
     */
    static function createQueryForTicket($ola = false)
    {

        $query = "";
        $test_status = PluginOlasCommonForm::getValue('status');

        if (isset($test_status[0]) && $test_status[0] != '0') {
            $query .= " and (status = ";
            for ($i = 0; $i < count($test_status); $i++) {
                $query .= " " . $test_status[$i] . " ";
                if ($i != count($test_status) - 1) {
                    $query .= "OR";
                }
            }
            $query .= ")";
        }
        if (PluginOlasCommonForm::getValue('type') != '0') {
            $query .= " and type = " . PluginOlasCommonForm::getValue('type');
        }
        if (PluginOlasCommonForm::getValue('date_min_open') != '' and PluginOlasCommonForm::getValue('date_max_open') != '') {
            $query .= " and `date` BETWEEN '" . PluginOlasCommonForm::getValue('date_min_open') . " 0:0:0' AND '" . PluginOlasCommonForm::getValue('date_max_open') . " 23:59:59'";
        }
        if (PluginOlasCommonForm::getValue('date_min_resolve') != '' and PluginOlasCommonForm::getValue('date_max_resolve') != '') {
            $query .= " and `solvedate` BETWEEN '" . PluginOlasCommonForm::getValue('date_min_resolve') . " 0:0:0' AND '" . PluginOlasCommonForm::getValue('date_max_resolve') . " 23:59:59'";
        }
		if($ola){

			if(PluginOlasCommonForm::getValue('slas_id') != '0'){
				$query .= " and slas_id = " . PluginOlasCommonForm::getValue('slas_id');
			}

		}
        /*$query .= getEntitiesRestrictRequest("AND",'ticket',"",$entity_restrict,
                $item->maybeRecursive());*/
        return $query;
    }

	/**
	 * This function is used to retrieve the exceeding percentage from dates.
	 *
	 * @param String $date_resolution : The ticket's resolve date
	 * @param String $date_echeance : The ticket's deadline
	 * @param String $date_ouverture : The ticket's open date
	 * @return float : Exceeding percentage
	 */
	static function pourcentageDepassement($date_resolution, $date_echeance, $date_ouverture){
			
		// Création de l'object dateTime
		try{
			$date_resolution = new DateTime($date_resolution);
			$date_echeance = new DateTime($date_echeance);
			$date_ouverture = new DateTime($date_ouverture);
		}
		catch (Exception $ex) {}
		// Transform to timestamp
		$ts_echeance = $date_echeance->getTimestamp();
		$ts_resolution = $date_resolution->getTimestamp();
		$ts_ouverture = $date_ouverture->getTimestamp();
	
		// Get percent
		$p1 = $ts_resolution - $ts_echeance;
		$p2 = $ts_echeance - $ts_ouverture;
		$result = ($p1/$p2)*100;
	
		return $result;
	
	}
	
	/**
	 * This function retrieve the percentage of tickets from a total number of tickets
	 *
	 * @param int $nb_tickets : Number of tickets
	 * @param int $nb_total_tickets : Total number of tickets
	 * @return number : percentage of tickets
	 */
	static function pourcentageTickets( $nb_tickets, $nb_total_tickets) {
		return round(($nb_tickets/$nb_total_tickets)*100, 2);
	}
	
	
	/**
	 * This function return the resolve time of a ticket
	 *
	 * @param String $date_resolution : Resolve date of the ticket
	 * @param String $date_ouverture : Open date of the ticket
	 * @return Object : DateInterval
	 */
	static function tempsResolutionTicket($date_resolution, $date_ouverture){
	
		try{
			$date_resolution = new DateTime($date_resolution);
			$date_ouverture = new DateTime($date_ouverture);
		}
		catch (Exception $ex) {}
	
		$interval = $date_ouverture ->diff($date_resolution);
		return $interval;
	}
	
	/**
	 * This function return the average resolve time
	 *
	 * @param array $temps_resolution_tickets : array of DateInterval
	 * @param unknown $nb_tickets : Number of tickets
	 * @return number : Average resolve time (in seconds)
	 */
	static function tempsMoyenResolution($total_secondes, $nb_tickets){
		
		$seconds = $total_secondes / $nb_tickets;
		
		$hours = floor($seconds / 3600);
		$mins = floor(($seconds - ($hours*3600)) / 60);
		$secs = floor($seconds % 60);
		
		$moyenne = $hours." ".__('Hour','olas')." ".$mins." ".__('Minute','olas')." ".$secs." ".__('Seconds','olas');
		
		return $moyenne;
	}
	
	/**
	 * This function return the average resolve time
	 *
	 * @param array $temps_resolution_tickets : array of DateInterval
	 * @param unknown $nb_tickets : Number of tickets
	 * @return number : Average resolve time (in seconds)
	 */
	static function secondsToTime($seconds){
	
		$hours = floor($seconds / 3600);
		$mins = floor(($seconds - ($hours*3600)) / 60);
		$secs = floor($seconds % 60);
	
		$time = $hours." ".__('Hour','olas')." ".$mins." ".__('Minute','olas')." ".$secs." ".__('Seconds','olas');
	
		return $time;
	}

	/**
	 * This function return an array from a glpi object (find)
	 *
	 * @param $class_name
	 * @param $query
	 * @return array
     */
	static function getObjectFromGlpi($class_name, $query){

		$object = new $class_name();
        if($query != '' and $class_name != "Log" and $class_name != "Group_Ticket"){
            $ent_query = PluginOlasCommon::checkEntityForReport();
            $query = $query." ".$ent_query;
            $object = $object->find($query);
        }else{
            $object = $object->find($query);
        }
		return $object;

	}

	/**
	 * This function return an object from glpi
	 *
	 * @param $class_name
	 * @param $query
	 * @return array
	 */
	static function getObjectFromDB($class_name, $id){

		$object = new $class_name();
		$object->getFromDB($id);
		return $object;

	}

	/**
	 * This function retrieve the excecution time of a ticket depending on the affialiated calendar.
	 *
	 * @param $calendar_id
	 * @param $start_date
	 * @param $end_date
	 * @return int|timestamp
     */
	static function getExecTime($calendar_id, $start_date, $end_date){

		// Is there a calendar on this OLA ?
		if ($calendar_id > 0){

			// In order to work the calendar need to be properly defined (Open and close time ...)
			$my_calendar = new Calendar();
			$my_calendar -> getFromDB($calendar_id);
			$exec_time = $my_calendar->getActiveTimeBetween($start_date, $end_date);

		}
		// If no calender is defined on this SLA use the default template (24/24 7/7 365d)
		else{

			$date  = strtotime($start_date);
			$solve_date = strtotime($end_date);
			$exec_time = $solve_date - $date;

		}
		return $exec_time;

	}

	/**
	 * This function return the exceeding percent depending on parameters
	 * (Execution time and resolve time)
	 *
	 * @param $exec_time
	 * @param $resolve_time
	 * @return float|int
     */
	static function getExceedingPercent($exec_time, $resolve_time){
		if ($exec_time > $resolve_time){
			$exceeding_percent =  round(($exec_time-$resolve_time)/$resolve_time*100, 2);
		}else{
			$exceeding_percent = 0;
		}
		return $exceeding_percent;
	}

	static function getAssocProblem($id_ticket){
		$t = PluginOlasCommon::getObjectFromGlpi("Problem_Ticket","tickets_id = $id_ticket");
		foreach ($t as $id_pb => $fields_pb) {
			$t = $fields_pb['id'];
		}
		if(empty($t)){
			$t = 'N/A';
		}
		return $t;
	}

	static function openCsvFile($table_id){
		if(PluginOlasCommonForm::getValue('csv') == $table_id){
			$csv_file = fopen("export.csv","w+");
			return $csv_file;
		}
	}

	static function addLineToCsv($table_id, $handle, $csv_line){
		if(PluginOlasCommonForm::getValue('csv') == $table_id){
			fwrite($handle, $csv_line);
		}
	}

	static function cleanTableId($table_id){
		$cara = array("<",">","="," ");
		$clean = str_replace($cara, "", $table_id);
		return $clean;
	}

    static function checkEntityForReport(){

        $common = new CommonDBTM();
        $current_entity = $_SESSION['glpiactive_entity'];

        if($common->checkEntity(true)){

            $sons = getSonsOf("glpi_entities",$current_entity);

            $return = "and entities_id IN (";
            $sons = implode(",",$sons);

            $return .= $sons;
            $return .= ")";

            return $return;

        }else{
            return "and entities_id = ".$current_entity;
        }
    }
	
}