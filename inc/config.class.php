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

class PluginOlasConfig extends CommonDBTM
{

    static $rightname = 'plugin_olas_config';

    static function getTypeName($nb = 0)
    {
        return _n('OLA', 'OLAs', $nb, 'Olas');
    }

    static function getMenuContent()
    {
        $menu = array();
        //Menu entry in config
        $menu['title'] = self::getTypeName(2);
        $menu['page'] = "/plugins/olas/front/config.php";
        $menu['links']['search'] = "/plugins/olas/front/config.php";

        if (Session::haveright(self::$rightname, UPDATE)) {
            $menu['links']['add'] = '/plugins/olas/front/config.form.php';
        }
        return $menu;
    }

    function post_getEmpty()
    {

        $this->fields['processing_time'] = 4;
        $this->fields['definition_time'] = 'hour';
    }

    public function showForm($ID, $options = array())
    {
        $rowspan = 4;
        if ($ID > 0) {
            $rowspan = 5;
        }

        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        echo "
            <tr class='tab_bg_1'>
                <td>" . __s('Name') . "</td>
                <td>";
        Html::autocompletionTextField($this, "name", array('value' => $this->fields["name"]));
        echo "
                </td>
                <td rowspan='" . $rowspan . "'>" . __s('Comments') . "</td>
                <td rowspan='" . $rowspan . "'>
                    <textarea cols='45' rows='8' name='comment' >" . $this->fields["comment"] . "</textarea>
                </td>
            </tr>";

        if ($ID > 0) {
            echo "
            <tr class='tab_bg_1'>
                <td>" . __s('Last update') . "</td>
                <td>" . ($this->fields["date_mod"] ? Html::convDateTime($this->fields["date_mod"]) : __s('Never')) . "</td>
            </tr>";
        }

        echo "
            <tr class='tab_bg_1'>
                <td>" . __s('Calendar') . "</td>
                <td>";
        Calendar::dropdown(array('value' => $this->fields["calendars_id"], 'emptylabel' => __s('24/7'), 'toadd' => array('-1' => __s('Calendar of the ticket'))));
        echo "
                </td>
            </tr>
            <tr class='tab_bg_1'>
                <td>" . __('Maximum time to process', 'olas') . "</td>
                <td>";
        Dropdown::showNumber("processing_time", array('value' => $this->fields["processing_time"], 'min' => 0));
        echo $this->addEndOfWorkingScript();
        echo "
                </td>
            </tr>
            <tr class='tab_bg_1'>
                <td>
                    <div id='title_endworkingday'>" . __s('End of working day') . "</div>
                </td>
                <td>
                    <div id='dropdown_endworkingday'>";
        Dropdown::showYesNo("end_of_working_day", $this->fields["end_of_working_day"]);
        echo "
                    </div>
                </td>
            </tr>
            <tr class='tab_bg_1'>
                <td>" . __('Support Center', 'olas') . " (" . __s('Group') . ")</td>
                <td>";
        Group::dropdown(array('value' => $this->fields["groups_id"]));
        echo "
                </td>
            </tr>
            <tr class='tab_bg_1'>
                <td>" . __s('SLA') . "</td>
                <td>";
        SLA::dropdown(array('value' => $this->fields["slas_id"]));
        echo "
                </td>
            </tr>";
        $this->showFormButtons($options);

        return true;
    }

    private function addEndOfWorkingScript()
    {
        $possible_values = array(
            'minute' => _n('Minute', 'Minutes', 2),
            'hour' => _n('Hour', 'Hours', 2),
            'day' => _n('Day', 'Days', 2));

        $rand = Dropdown::showFromArray('definition_time', $possible_values, array('value' => $this->fields["definition_time"], 'on_change' => 'appearhideendofworking()'));

        $ret[] = "<script type='text/javascript' >";
        $ret[] = "function appearhideendofworking() {";
        $ret[] = "if ($('#dropdown_definition_time$rand option:selected').val() == 'day') {
         $('#title_endworkingday').show();
         $('#dropdown_endworkingday').show();
      } else {
         $('#title_endworkingday').hide();
         $('#dropdown_endworkingday').hide();
      }";
        $ret[] = "}";
        $ret[] = "appearhideendofworking();";
        $ret[] = "</script>";

        return "\n" . implode("\n", $ret) . "\n";
    }

    public function getSearchOptions()
    {

        $tab = array();
        $tab['common'] = __s('Characteristics');

        $tab[1]['table'] = $this->getTable();
        $tab[1]['field'] = 'name';
        $tab[1]['name'] = __s('Name');
        $tab[1]['datatype'] = 'itemlink';
        $tab[1]['massiveaction'] = false;

        $tab[2]['table'] = $this->getTable();
        $tab[2]['field'] = 'id';
        $tab[2]['name'] = __s('ID');
        $tab[2]['massiveaction'] = false;
        $tab[2]['datatype'] = 'number';

        $tab[4]['table'] = 'glpi_calendars';
        $tab[4]['field'] = 'name';
        $tab[4]['name'] = __s('Calendar');
        $tab[4]['datatype'] = 'dropdown';

        $tab[5]['table'] = $this->getTable();
        $tab[5]['field'] = 'processing_time';
        $tab[5]['name'] = __('Maximum processing time', 'olas');
        $tab[5]['datatype'] = 'specific';
        $tab[5]['massiveaction'] = false;
        $tab[5]['nosearch'] = true;
        $tab[5]['additionalfields'] = array('definition_time');

        $tab[6]['table'] = $this->getTable();
        $tab[6]['field'] = 'end_of_working_day';
        $tab[6]['name'] = __s('End of working day');
        $tab[6]['datatype'] = 'bool';
        $tab[6]['massiveaction'] = false;

        $tab[71]['table'] = 'glpi_groups';
        $tab[71]['field'] = 'completename';
        $tab[71]['datatype'] = 'dropdown';
        $tab[71]['name'] = __s('Requester group');
        $tab[71]['forcegroupby'] = true;
        $tab[71]['massiveaction'] = false;

        $tab[30]['table'] = 'glpi_slas';
        $tab[30]['field'] = 'name';
        $tab[30]['name'] = __s('SLA');
        $tab[30]['massiveaction'] = false;
        $tab[30]['datatype'] = 'dropdown';

        $tab[16]['table'] = $this->getTable();
        $tab[16]['field'] = 'comment';
        $tab[16]['name'] = __s('Comments');
        $tab[16]['datatype'] = 'text';

        $tab[80]['table'] = 'glpi_entities';
        $tab[80]['field'] = 'completename';
        $tab[80]['name'] = __s('Entity');
        $tab[80]['massiveaction'] = false;
        $tab[80]['datatype'] = 'dropdown';

        $tab[86]['table'] = $this->getTable();
        $tab[86]['field'] = 'is_recursive';
        $tab[86]['name'] = __s('Child entities');
        $tab[86]['datatype'] = 'bool';

        return $tab;
    }

    public function install(Migration $migration)
    {
        global $DB;

        // install
        if (!TableExists($this->getTable())) {

            $query = "CREATE TABLE `" . $this->getTable() . "`(
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) DEFAULT NULL,
                    `entities_id` int(11) NOT NULL DEFAULT '0',
                    `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                    `comment` text,
                    `processing_time` int(11) NOT NULL,
                    `definition_time` varchar(255) DEFAULT NULL,
                    `end_of_working_day` tinyint(1) NOT NULL DEFAULT '0',
                    `date_mod` datetime DEFAULT NULL,
                    `calendars_id` int(11) NOT NULL DEFAULT '0',
                    `groups_id` int(11) NOT NULL DEFAULT '0',
                    `slas_id` int(11) NOT NULL DEFAULT '0',
                    PRIMARY KEY  (`id`)
                   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
            $DB->queryOrDie($query, sprintf(__("Error in creating %s table", 'olas'), $this->getTable()) .
                "<br>".$DB->error());

        } else {
            // Upgrade
        }

        return true;
    }

    public function uninstall()
    {
        global $DB;

        if (TableExists($this->getTable())) {
            $query = "DROP TABLE `" . $this->getTable() . "`";
            $DB->queryOrDie($query, $DB->error());
        }

        return true;
    }
}
