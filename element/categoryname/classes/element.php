<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
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
 * This file contains the customcert element categoryname's core interaction API.
 *
 * @package    customcertelement_categoryname
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customcertelement_categoryname;

/**
 * The customcert element categoryname's core interaction API.
 *
 * @package    customcertelement_categoryname
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \mod_customcert\element {

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     */
    public function render($pdf, $preview, $user) {
        \mod_customcert\element_helper::render_content($pdf, $this, $this->get_category_name());
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     *
     * @return string the html
     */
    public function render_html() {
        return \mod_customcert\element_helper::render_html_content($this, $this->get_category_name());
    }

    /**
     * Helper function that returns the category name.
     *
     * @return string
     */
    protected function get_category_name(): string {
        global $DB, $SITE;

        $courseid = \mod_customcert\element_helper::get_courseid($this->get_id());
        $course = get_course($courseid);
        $context = \mod_customcert\element_helper::get_context($this->get_id());

        // Check that there is a course category available.
        if (!empty($course->category)) {
            $categoryname = $DB->get_field('course_categories', 'name', ['id' => $course->category], MUST_EXIST);
        } else { // Must be in a site template.
            $categoryname = $SITE->fullname;
        }

        return format_string($categoryname, true, ['context' => $context]);
    }
}
