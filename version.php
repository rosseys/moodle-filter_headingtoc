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
 * Version details for the filter_headingtoc plugin.
 *
 * @package    filter_headingtoc
 * @copyright  2026 Roxana Castillo (Rosseys)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2026080100;        // The current plugin version (YYYYMMDDXX).
$plugin->requires  = 2024100700;        // Requires Moodle 4.5 LTS or later.
$plugin->supported = [405, 502];        // Supported Moodle branch range (4.5 LTS to 5.2).
$plugin->component = 'filter_headingtoc'; // Full name of the plugin.
$plugin->maturity  = MATURITY_STABLE;   // Stable release.
$plugin->release   = '1.0.1';
