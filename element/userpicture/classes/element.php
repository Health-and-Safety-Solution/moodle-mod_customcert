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
 * This file contains the customcert element userpicture's core interaction API.
 *
 * @package    customcertelement_userpicture
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customcertelement_userpicture;

/**
 * The customcert element userpicture's core interaction API.
 *
 * @package    customcertelement_userpicture
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \mod_customcert\element {

    /**
     * This function renders the form elements when adding a customcert element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function render_form_elements($mform) {
        \mod_customcert\element_helper::render_form_element_width($mform);

        \mod_customcert\element_helper::render_form_element_height($mform);

        if (get_config('customcert', 'showposxy')) {
            \mod_customcert\element_helper::render_form_element_position($mform);
        }
    }

    /**
     * Performs validation on the element values.
     *
     * @param array $data the submitted data
     * @param array $files the submitted files
     * @return array the validation errors
     */
    public function validate_form_elements($data, $files) {
        // Array to return the errors.
        $errors = [];

        // Validate the width.
        $errors += \mod_customcert\element_helper::validate_form_element_width($data);

        // Validate the height.
        $errors += \mod_customcert\element_helper::validate_form_element_height($data);

        // Validate the position.
        if (get_config('customcert', 'showposxy')) {
            $errors += \mod_customcert\element_helper::validate_form_element_position($data);
        }

        return $errors;
    }

    /**
     * This will handle how form data will be saved into the data column in the
     * customcert_elements table.
     *
     * @param \stdClass $data the form data
     * @return string the json encoded array
     */
    public function save_unique_data($data) {
        // Array of data we will be storing in the database.
        $arrtostore = [
            'width' => (int) $data->width,
            'height' => (int) $data->height,
        ];

        return json_encode($arrtostore);
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     */
    public function render($pdf, $preview, $user) {
        global $CFG;

        // If there is no element data, we have nothing to display.
        if (empty($this->get_data())) {
            return;
        }

        $imageinfo = json_decode($this->get_data());

        $context = \context_user::instance($user->id);

        // Get files in the user icon area.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'user', 'icon', 0);

        // Get the file we want to display.
        $file = null;
        foreach ($files as $filefound) {
            if (!$filefound->is_directory()) {
                $file = $filefound;
                break;
            }
        }

	//Begin Customisation: Print Company Seal/Logo in certificate generated for the company
	global $DB;
	if ($company_users = $DB->get_record('company_users', ['userid' => $user->id])) {
		$preview = '';
		$sitecontext = \context_system::instance();
		$fs = get_file_storage();
		if ($files = $fs->get_area_files($sitecontext->id, 'local_iomad', 'companycertificateseal', $company_users->companyid, 'sortorder DESC, id DESC', false)) {
			if (!count($files) < 1) {
			        $file = reset($files);
		        	unset($files);
			}
		}
	}
	//End Customisation

        // Show image if we found one.
        if ($file) {
            $location = make_request_directory() . '/target';
            $file->copy_content_to($location);
            $pdf->Image($location, $this->get_posx(), $this->get_posy(), $imageinfo->width, $imageinfo->height);
        } else if ($preview) { // Can't find an image, but we are in preview mode then display default pic.
            $location = $CFG->dirroot . '/pix/u/f1.png';
            $pdf->Image($location, $this->get_posx(), $this->get_posy(), $imageinfo->width, $imageinfo->height);
        }
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
        global $PAGE, $USER;

        // If there is no element data, we have nothing to display.
        if (empty($this->get_data())) {
            return '';
        }

        $imageinfo = json_decode($this->get_data());

        // Get the image.
        $userpicture = new \user_picture($USER);
        $userpicture->size = 1;
        $url = $userpicture->get_url($PAGE)->out(false);

        // The size of the images to use in the CSS style.
        $style = '';
        if ($imageinfo->width === 0 && $imageinfo->height === 0) {
            // Put this in so code checker doesn't complain.
            $style .= '';
        } else if ($imageinfo->width === 0) { // Then the height must be set.
            $style .= 'width: ' . $imageinfo->height . 'mm; ';
            $style .= 'height: ' . $imageinfo->height . 'mm';
        } else if ($imageinfo->height === 0) { // Then the width must be set.
            $style .= 'width: ' . $imageinfo->width . 'mm; ';
            $style .= 'height: ' . $imageinfo->width . 'mm';
        } else { // Must both be set.
            $style .= 'width: ' . $imageinfo->width . 'mm; ';
            $style .= 'height: ' . $imageinfo->height . 'mm';
        }

        return \html_writer::tag('img', '', ['src' => $url, 'style' => $style]);
    }

    /**
     * Sets the data on the form when editing an element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function definition_after_data($mform) {
        // Set the image, width and height for this element.
        if (!empty($this->get_data())) {
            $imageinfo = json_decode($this->get_data());

            $element = $mform->getElement('width');
            $element->setValue($imageinfo->width);

            $element = $mform->getElement('height');
            $element->setValue($imageinfo->height);
        }

        parent::definition_after_data($mform);
    }
}
