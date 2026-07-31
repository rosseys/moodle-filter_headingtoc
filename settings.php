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
 * Settings for the filter_headingtoc plugin.
 *
 * @package    filter_headingtoc
 * @copyright  2026 Roxana Castillo (Rosseys)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'filter_headingtoc/levels',
        get_string('setting_levels', 'filter_headingtoc'),
        get_string('setting_levels_desc', 'filter_headingtoc'),
        'h2,h3,h4',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'filter_headingtoc/title',
        get_string('setting_title', 'filter_headingtoc'),
        get_string('setting_title_desc', 'filter_headingtoc'),
        get_string('toctitle', 'filter_headingtoc'),
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'filter_headingtoc/autotop',
        get_string('setting_autotop', 'filter_headingtoc'),
        get_string('setting_autotop_desc', 'filter_headingtoc'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'filter_headingtoc/minheadings',
        get_string('setting_minheadings', 'filter_headingtoc'),
        get_string('setting_minheadings_desc', 'filter_headingtoc'),
        3,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'filter_headingtoc/numbered',
        get_string('setting_numbered', 'filter_headingtoc'),
        get_string('setting_numbered_desc', 'filter_headingtoc'),
        0
    ));
}
