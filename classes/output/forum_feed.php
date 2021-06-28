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
 * Block forum_feed is defined here.
 *
 * @package     block_forum_feed
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_forum_feed\output;
defined('MOODLE_INTERNAL') || die();

use context_course;
use context_helper;
use context_module;
use core_course\external\course_summary_exporter;
use core_user\output\myprofile\category;
use moodle_url;
use renderable;
use renderer_base;
use templatable;
use user_picture;

/**
 * Block forum_feed is defined here.
 *
 * @package     block_forum_feed
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forum_feed implements renderable, templatable {
    /**
     * @var $forumid
     */
    protected $forumid = null;

    /**
     * @var $forumposts
     */
    protected $forumposts = [];

    /**
     * @var int
     */
    protected $maxitemcount = 0;
    /**
     * @var int
     */
    protected $maxtextlength = false;

    /**
     * forum_feed constructor.
     * Retrieve matching forum posts sorted in reverse order
     *
     * @param int $forumid
     * @param int $maxitemcount
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct($forumid, $maxitemcount = 3, $maxtextlength = false) {

        $this->forumid = $forumid;
        $this->maxitemcount = $maxitemcount;

        $this->maxtextlength = $maxtextlength;
    }

    /**
     * Export featured course data
     *
     * @param renderer_base $renderer
     * @return array
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $renderer) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/forum/lib.php');   // We'll need this.
        $forumposts = [];
        $text = '';
        $forum = $DB->get_record('forum', array('id' => $this->forumid));
        if ($forum) {
            $modinfo = get_fast_modinfo($forum->course);
            if (!empty($modinfo->instances['forum'][$forum->id])) {


                $cm = $modinfo->instances['forum'][$forum->id];

                // Check if visible.
                if ($cm->uservisible) {

                    $context = context_module::instance($cm->id);

                    /// User must have perms to view discussions in that forum
                    if (has_capability('mod/forum:viewdiscussion', $context)) {

                        /// First work out whether we can post to this group and if so, include a link
                        $groupmode = groups_get_activity_groupmode($cm);
                        $currentgroup = groups_get_activity_group($cm, true);
                        $sort = forum_get_default_sort_order(true, 'p.modified', 'd', false);
                        if ($discussions = forum_get_discussions($cm, $sort, true,
                            -1, $this->maxitemcount,
                            false, -1, 0, FORUM_POSTS_ALL_USER_GROUPS)) {
                            foreach ($discussions as $discussion) {
                                $post = new \stdClass();
                                if ($groupmode && $discussion->groupid > 0) {
                                    $group = groups_get_group($discussion->groupid);
                                    $post->subject = format_string($group->name, true, $forum->course);
                                    $post->subjectlink = (new moodle_url($CFG->wwwroot . '/group/overview.php',
                                        array('id' => $cm->course, 'groupid'=>$group->id)))->out(false);
                                } else {
                                    $post->subject = format_string($discussion->name, true, $forum->course);
                                    $post->subjectlink = (new moodle_url($CFG->wwwroot . '/mod/forum/discuss.php',
                                        array('d' => $discussion->discussion)))->out(false);
                                }
                                $posttime = $discussion->modified;
                                if (!empty($CFG->forum_enabletimedposts) && ($discussion->timestart > $posttime)) {
                                    $posttime = $discussion->timestart;
                                }
                                $user = \core_user::get_user($discussion->userid);
                                $userpicture = new user_picture($user);
                                $userpicture->size = 50;
                                $post->userpicture = $renderer->render($userpicture);
                                $post->userfullname = fullname($discussion);
                                $post->timestamp = $posttime;
                                $message = content_to_text($discussion->message, $discussion->messageformat);
                                if ($this->maxtextlength > 0 && (strlen($message) > $this->maxtextlength)) {
                                    $message = \core_text::substr($message, 0, $this->maxtextlength) . '...';
                                }
                                $post->message = $message;
                                $post->morelink = new moodle_url($CFG->wwwroot . '/mod/forum/discuss.php',
                                    array('d' => $discussion->discussion),
                                    "p{$discussion->id}");
                                $forumposts[] = $post;
                            }
                        }
                    }
                }
            }
        }

        $exportedvalue = [
            'posts' => array_values($forumposts),
        ];
        return $exportedvalue;
    }
}