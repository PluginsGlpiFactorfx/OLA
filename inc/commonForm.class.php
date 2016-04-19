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
class PluginOlasCommonForm
{

    // Maximum pieces
    static private $max_pieces = 6;

    static private $ola = false;

    /**
     * @return boolean
     */
    public static function isOla()
    {
        return self::$ola;
    }

    /**
     * @param boolean $ola
     * @return PluginOlasCommonForm
     */
    public static function setOla($ola)
    {
        self::$ola = $ola;
    }

    /**
     * Add default dates if none is set.
     *&
     * @param $get_min
     * @param $get_max
     */
    static function checkForDefaultDate($get_min, $get_max)
    {

        if (empty(self::getValue($get_min)) and empty(self::getValue($get_max))) {

            $date1 = new DateTime();
            $date1->sub(new DateInterval("P1Y"));
            $_GET[$get_min] = $date1->format('Y-m-d');

            $date2 = new DateTime();
            $_GET[$get_max] = $date2->format('Y-m-d');

        }
    }

    /**
     *  Generate All sections for form
     */
    static function showSections()
    {
        global $CFG_GLPI;

        echo Html::script($CFG_GLPI["root_doc"] . '/plugins/olas/js/sections.js');
        $display = true;
        for ($i = 1; $i <= self::$max_pieces; $i++) {
            $section = is_array(self::getValue("sections")) ? self::getValue("sections")[$i] : 0;
            $display = ($display !== false) ? ($section != 0) : false;
            echo "<tr " . (($display) ? "" : "style='display: none'") . "id='section_$i'><td class='right'>" . sprintf(__('Section n° %d', 'olas'), $i) . "</td>
              <td>";
            $rand = Dropdown::showNumber("sections[$i]", array(
                'value' => $display ? $section : 0,
                'min' => 0,
                'max' => 100,
                'step' => 1));
            echo "
              <img alt='' id='add_section_" . $i . "_$rand' title='" . __s('Add') . "' src='" . $CFG_GLPI["root_doc"] .
                "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'/>";
            if ($i > 1) {
                echo "
               <img alt='' id='sub_section_" . $i . "_$rand' title='" . __s('Delete') . "' src='" . $CFG_GLPI["root_doc"] .
                    "/pics/sub_dropdown.png' style='cursor:pointer; margin-left:2px;'/>";
            }
            echo "</td></tr>";
        }
    }

    /**
     * Return value from $_GET or $_POST, if not present return $default
     *
     * @param $index
     * @param string $default
     * @param string $method
     * @return bool|string
     */
    static function getValue($index, $default = '', $method = 'get')
    {
        global $_GET, $_POST;

        if ($method == 'get' && !isset($_GET[$index]) || $method == 'post' && !isset($_POST[$index])) {
            return $default;
        } elseif ($method == 'get' && isset($_GET[$index])) {
            return $_GET[$index];
        } elseif ($method == 'post' && isset($_POST[$index])) {
            return $_POST[$index];
        }

        return false;
    }

    /**
     * Get all status from tickets
     *
     * @return array
     */
    static function getAllTicketStatus()
    {
        return array_merge(array("0" => __s('All')), Ticket::getAllStatusArray());
    }

    /**
     * Get all tickets types
     *
     * @return array
     */
    static function getAllTicketType()
    {
        return array_merge(array("0" => __s('All')), Ticket::getTypes());
    }

    /**
     * Generate filter for reports SLAS / OLAS
     */
    static function getHeader()
    {

        // Set default date
        self::checkForDefaultDate("date_min_open", "date_max_open");

        // Filter form
        echo "
        <div class='center'>
            <form method='get' id='form_ola_sla' name='form' action=''>
                <table class='tab_cadre_fixe'>
                    <tr >
                        <td class='center' colspan=4>" . __('Open ticket date', 'olas') . "</td>
                    </tr>
                    <tr>
                        <td width=20% class='right'>" . __s('Start date') . "</td>
                        <td>";
        Html::showDateField("date_min_open", array('value' => self::getValue('date_min_open')));
        echo "
                        </td>
                        <td class='right'>" . __s('End date') . "</td>
                        <td>";
        Html::showDateField("date_max_open", array('value' => self::getValue('date_max_open')));
        echo "
                        </td>
                    </tr>
                    <tr>
                        <td class='center' colspan=4>" . __('Resolve ticket date', 'olas') . "</td>
                    </tr>
                    <tr>
                        <td class='right'>" . __s('Start date') . "</td>
                        <td>";
        Html::showDateField("date_min_resolve", array('value' => self::getValue('date_min_resolve')));
        echo "
                        </td>
                        <td class='right'>" . __s('End date') . "</td>
                        <td>";
        Html::showDateField("date_max_resolve", array('value' => self::getValue('date_max_resolve')));
        echo "
                        </td>
                    </tr>
                    <tr>
                        <td class='center' colspan=4>" . __('Filters', 'olas') . "</td>
                    </tr>
                    <tr>
                        <td class='right' >" . __('Ticket statut', 'olas') . "</td>
                        <td colspan=4>";
        Dropdown::showFromArray('ticket_statut', self::getAllTicketStatus(), array('values' => self::getValue('ticket_statut', array(0)), 'multiple' => true));
        echo "
                        </td>
                    </tr>
                    <tr>
                        <td class='right'>" . __('SLA (name)', 'olas') . "</td>
                        <td>";
        Sla::dropdown(array('value' => self::getValue("slas_id"), 'emptylabel' => __s('All')));
        echo "
                        </td>
                    </tr>
                    <tr>
                        <td class='right'>" . __('Ticket type', 'olas') . "</td>
                        <td>";
        Dropdown::showFromArray('type', self::getAllTicketType(), array('value' => self::getValue('type')));
        echo "
                        </td>
                    </tr>";
        if (self::isOla()) {
            echo "
                    <tr>
                        <td class='right'>" . __('Support Center', 'olas') . "</td>
                        <td>";
            Group::dropdown(array('value' => self::getValue("groups_id"), 'emptylabel' => __s('All'), 'addicon' => false));
            echo "      </td>
                    </tr>

            ";
        }
        echo "
                    <tr>
                        <td class='right'>" . __('Details / Group by exceeding percent', 'olas') . "</td>
                        <td>";
        Dropdown::showFromArray('details', array(__s('No'), __s('Yes')), array('value' => self::getValue('details')));
        echo "
                        </td>
                    </tr>
                        ";
        self::showSections();
        echo "
                    <tr id='tr_details'>";
        echo "
                    </tr>
                    <tr>
                        <td colspan=5 align=center><input type='submit' class='submit' name='submit' value=\"" . __s('Display report') . "\"></td>
                    </tr>
                </table>
            </form>";
        echo "
        </div>";
    }

}