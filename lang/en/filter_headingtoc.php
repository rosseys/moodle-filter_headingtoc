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
 * Language strings for the filter_headingtoc plugin.
 *
 * @package    filter_headingtoc
 * @copyright  2026 Roxana Castillo (Rosseys)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['filtername'] = 'Heading Contents';
$string['pluginname'] = 'Heading Contents';
$string['privacy:metadata'] = 'The Heading Contents filter does not store any personal data.';
$string['setting_autotop'] = 'Insert at top when no marker is present';
$string['setting_autotop_desc'] = 'If enabled, a table of contents is inserted at the start of content that has enough headings but no [toc] marker.';
$string['setting_levels'] = 'Heading levels';
$string['setting_levels_desc'] = 'Which heading tags become entries in the table of contents. Comma-separated, for example h2,h3,h4.';
$string['setting_minheadings'] = 'Minimum headings';
$string['setting_minheadings_desc'] = 'Do not generate a table of contents unless the content has at least this many qualifying headings.';
$string['setting_numbered'] = 'Numbered list';
$string['setting_numbered_desc'] = 'Render the table of contents as a numbered list instead of bullets.';
$string['setting_title'] = 'Contents title';
$string['setting_title_desc'] = 'Text shown as the heading of the generated table of contents.';
$string['toctitle'] = 'Contents';
