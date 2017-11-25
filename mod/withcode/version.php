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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * This page lists all the instances of wiki in a particular course
 *
 * @package withcode
 * @copyright 2016 pddring blog.withcode.uk
 *
 * @author Pete Dring
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
 
$plugin->version   = 2017112500;
$plugin->requires  = 2014051200;
$plugin->cron      = 0;
$plugin->component = 'mod_withcode';
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.2	';