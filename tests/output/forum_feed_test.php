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
namespace block_forum_feed\output;

use advanced_testcase;

/**
 * Class block_forum_feed
 *
 * @package     block_forum_feed
 * @copyright   2023 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forum_feed_test extends advanced_testcase {
    /**
     * @var \stdClass $course
     */
    protected $course;
    /**
     * @var \stdClass $coursemodule
     */
    protected $forum;

    /**
     * @var array $student
     */
    protected $users;

    /**
     * @var array $discussions
     */
    protected $discussions;

    /**
     * Data generator for forum_feed tests
     *
     * @return array[]
     */
    public static function forum_feed_data_generator(): array {
        return [
            'basic use case' => [
                'userview' => 'teacher',
                'maxitemcount' => 3,
                'expectedcount' => 3,
            ],
            'basic use case with maxitemcount' => [
                'userview' => 'teacher',
                'maxitemcount' => 50,
                'expectedcount' => 10,
            ],
            'non registered user' => [
                'userview' => 'nonreguser',
                'maxitemcount' => 3,
                'expectedcount' => 0,
            ],
        ];
    }

    /**
     * Setup the test
     *
     * @return void
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $datagenerator = $this->getDataGenerator();
        $this->course = $datagenerator->create_course();
        $this->users = [];
        $this->users['student'] = $datagenerator->create_and_enrol($this->course, 'student');
        $this->users['teacher'] = $datagenerator->create_and_enrol($this->course, 'teacher');
        $this->users['nonreguser'] = $datagenerator->create_user();
        $this->forum = $datagenerator->create_module('forum', ['course' => $this->course->id]);
        // Add discussions to course 1 started by user1.
        $this->discusions = [];
        for ($i = 0; $i < 10; $i++) {
            $this->discusions[] = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion((object) [
                'course' => $this->course->id,
                'userid' => $this->users['student']->id,
                'forum' => $this->forum->id,
            ]);
        }

    }

    /**
     * Test course I teach filter
     *
     * @param string $username
     * @param int $maxitemcount
     * @param int $expectedcount
     *
     * @covers       \block_forum_feed\output\forum_feed::export_for_template
     * @dataProvider forum_feed_data_generator
     */
    public function test_basic_display(string $username, int $maxitemcount, int $expectedcount) {
        global $PAGE;
        $this->resetAfterTest();
        $renderer = $PAGE->get_renderer('core');
        $forumfeed = new forum_feed($this->forum->id, $maxitemcount);
        $this->setUser($this->users[$username]);
        $data = $forumfeed->export_for_template($renderer);

        $this->assertCount($expectedcount, $data['posts']);
    }

}
