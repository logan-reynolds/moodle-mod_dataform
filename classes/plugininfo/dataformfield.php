<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Subplugin info class.
 *
 * @package   mod_dataform
 * @copyright 2014 Itamar Tzadok {@link http://substantialmethods.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_dataform\plugininfo;

use core\plugininfo\base;

defined('MOODLE_INTERNAL') || die();

class dataformfield extends base {
    /**
     * Finds all enabled plugins, the result may include missing plugins.
     * @return array|null of enabled plugins $pluginname=>$pluginname, null means unknown
     */
    public static function get_enabled_plugins() {
        if (!$enabled = get_config('mod_dataform', 'enabled_dataformfield')) {
            return null;
        }

        $enabled = explode(',', $enabled);
        return array_combine($enabled, $enabled);
    }

    public function is_enabled() {
        $enabled = get_config('mod_dataform', 'enabled_dataformfield');
        if (!$enabled) {
            return false;
        }

        $enabled = array_flip(explode(',', $enabled));
        return isset($enabled[$this->name]);
    }

    public function is_uninstall_allowed() {
        return true;
    }

    public function get_settings_section_name() {
        return 'dataformfieldsetting' . $this->name;
    }

    public function load_settings(part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE; // In case settings.php wants to refer to them.
        $ADMIN = $adminroot; // May be used in settings.php.
        $section = $this->get_settings_section_name();

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig or !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $settings = new admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);
        include($this->full_path('settings.php'));

        if ($settings) {
            $ADMIN->add($parentnodename, $settings);
        }
    }

    public static function get_manage_url() {
        return new \moodle_url('/admin/settings.php', array('section' => 'managedataformfield'));
    }

    public function uninstall_cleanup() {
        $enabled = get_config('mod_dataform', 'enabled_dataformfield');
        if ($enabled) {
            $enabled = array_flip(explode(',', $enabled));
            unset($enabled[$this->name]);
            $enabled = array_flip($enabled);
            set_config('enabled_dataformfield', implode(',', $enabled), 'mod_dataform');
        }

        parent::uninstall_cleanup();
    }

    /**
     * Extra warning before uninstallation:
     * - Plugin has instances.
     *
     * @return string
     */
    public function get_uninstall_extra_warning() {
        global $OUTPUT;

        if ($instancecount = $this->get_instance_count()) {
            $warning = get_string('pluginhasinstances', 'mod_dataform', $instancecount);
            return $OUTPUT->notification($warning, 'notifyproblem');
        }
    }

    /**
     * Hook method to implement certain steps when uninstalling the plugin.
     *
     * This hook is called by {@link core_plugin_manager::uninstall_plugin()} so
     * it is basically usable only for those plugin types that use the default
     * uninstall tool provided by {@link self::get_default_uninstall_url()}.
     *
     * @param \progress_trace $progress traces the process
     * @return bool true on success, false on failure
     */
    public function uninstall(\progress_trace $progress) {
        // Delete instances.
        $this->delete_instances();

        return true;
    }

    /**
     *
     */
    public function get_instance_count() {
        global $DB;

        if (!isset($this->instances)) {
            $this->instances = $DB->count_records('dataform_fields', array('type' => $this->name));
        }

        return $this->instances;
    }

    /**
     * Deletes plugin instances.
     *
     * @return void
     */
    public function delete_instances() {
        global $DB;

        $params = array('type' => $this->name);
        if (!$instances = $DB->get_records_menu('dataform_fields', $params, 'dataid', 'id,dataid')) {
            return;
        }

        $bydataform = array();
        foreach ($instances as $instanceid => $dataformid) {
            if (empty($bydataform[$dataformid])) {
                $bydataform[$dataformid] = array();
            }

            $bydataform[$dataformid][] = $instanceid;
        }

        foreach ($bydataform as $dataformid => $instanceids) {
            $entryman = \mod_dataform_field_manager::instance($dataformid);
            $entryman->process_fields('delete', $instanceids, true);
        }
    }
}
