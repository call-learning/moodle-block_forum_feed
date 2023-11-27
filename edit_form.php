<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.
/**
 * Form for editing block_forum_feed block instances.
 *
 * @package    block_forum_feed
 * @copyright  2021 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_forum_feed_edit_form extends block_edit_form {

    /**
     * Specific definition for the form
     *
     * @param MoodleQuickForm $mform
     *
     * Extends the configuration form for block_forum_feed.
     */
    protected function specific_definition($mform) {
        global $DB;
        // Section header title.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
        $mform->addElement('text',
            'config_title',
            get_string('title', 'block_forum_feed')
        );
        $mform->setDefault('config_title', get_string('pluginname', 'block_forum_feed'));
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('text',
            'config_maxfeed',
            get_string('maxfeed', 'block_forum_feed')
        );
        $mform->setDefault('config_maxfeed', 3);
        $mform->setType('config_maxfeed', PARAM_INT);
        $sql = "SELECT f.id, ". $DB->sql_concat_join("'/'", ['f.name', 'c.fullname'])
            . " FROM {forum} f LEFT JOIN {course} c ON f.course = c.id";
        $forumlist = $DB->get_records_sql_menu($sql);
        $mform->addElement('searchableselector', 'config_forumid',
            get_string('forumid', 'block_forum_feed'),
            $forumlist
        );
        $mform->setType('config_forumid', PARAM_INT);
        $mform->addElement('text',
            'config_maxtextlength',
            get_string('maxtextlength', 'block_forum_feed')
        );
        $mform->setDefault('config_maxtextlength', 0);
        $mform->setType('config_maxtextlength', PARAM_INT);
    }
}
