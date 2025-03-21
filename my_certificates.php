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
 * Handles viewing the certificates for a certain user.
 *
 * @package    mod_customcert
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$userid = optional_param('userid', $USER->id, PARAM_INT);
$download = optional_param('download', null, PARAM_ALPHA);
$courseid = optional_param('course', null, PARAM_INT);
$downloadcert = optional_param('downloadcert', '', PARAM_BOOL);
if ($downloadcert) {
    $certificateid = required_param('certificateid', PARAM_INT);
    $customcert = $DB->get_record('customcert', ['id' => $certificateid], '*', MUST_EXIST);

    // Check there exists an issued certificate for this user.
    if (!$issue = $DB->get_record('customcert_issues', ['userid' => $userid, 'customcertid' => $customcert->id])) {
        throw new moodle_exception('You have not been issued a certificate');
    }

    //Begin Customisation: Accellier: Check if user allowed to download certificate in this company or course
    if (!(has_capability('mod/customcert:viewallcertificates', context_system::instance()))) {
    $donotemailcert = 0;
    $user_companys = $DB->get_records('company_users', ['userid' => $USER->id]);
    foreach ($user_companys as $user_company) {
    	$companydetails = $DB->get_record('company', ['id' => $user_company->companyid]);
        if (!(empty($companydetails->custom1))) {
        	if ($user_company->managertype <= 0) {
			$donotemailcert = 1;
        	}
        }
    }
    if ($donotemailcert == 1) {
	throw new moodle_exception('Your certificate will be emailed to your course booker/employer.', 'Information');
    }
    /*$context = context_system::instance();
    $companyid = iomad::get_my_companyid($context);
    $companydetails = $DB->get_record('company', ['id' => $companyid]);
    if (!(empty($companydetails->custom1))) {
	$if_manager = $DB->get_record('company_users', ['userid' => $USER->id, 'companyid' => $companyid]);
    	if ($if_manager->managertype <= 0) {
		throw new moodle_exception('You are not allowed to download the certificate. Please contact the admin team.', 'Certificate');
	}
    }*/
    $cooursecertdetails = $DB->get_record('customfield_data', ['fieldid' => 6, 'instanceid' => $customcert->course]);
    if (!(empty($cooursecertdetails->value))) {
        throw new moodle_exception('Your certificate will be emailed to your course booker/employer.', 'Information');
    }
    }
    //End Customisation
}
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', \mod_customcert\certificate::CUSTOMCERT_PER_PAGE, PARAM_INT);
$pageurl = $url = new moodle_url('/mod/customcert/my_certificates.php', ['userid' => $userid,
    'page' => $page, 'perpage' => $perpage]);

// Requires a login.
if ($courseid) {
    require_login($courseid);
} else {
    require_login();
}

// Check that we have a valid user.
$user = \core_user::get_user($userid, '*', MUST_EXIST);
//Begin Customisation: Accellier: Check if manager then allow to download certificates only of the related company
$context = context_system::instance();
$companyid = iomad::get_my_companyid($context);
//End Customisation

//Begin Customisation: Accellier: Check if user is allowed to download certificates
if (($userid == $USER->id) && !has_capability('mod/customcert:view', context_system::instance())) {
	throw new moodle_exception('You are not allowed to view the certificates');
}
//End Customization

// If we are viewing certificates that are not for the currently logged in user then do a capability check.
if (($userid != $USER->id) && !has_capability('mod/customcert:viewallcertificates', context_system::instance())) {
    //Begin Customisation: Accellier: Check if manager then allow to download certificates only of the related company
    $if_manager = $DB->get_record('company_users', ['userid' => $USER->id, 'companyid' => $companyid]);
    if ($if_manager->managertype <= 0) {
	throw new moodle_exception('You are not allowed to view these certificates');
    }
    //End Customisation
}

$PAGE->set_url($pageurl);
$PAGE->set_context(context_user::instance($userid));
$PAGE->set_title(get_string('mycertificates', 'customcert'));
$PAGE->set_pagelayout('standard');
$PAGE->navigation->extend_for_user($user);

// Check if we requested to download a certificate.
if ($downloadcert) {
    $template = $DB->get_record('customcert_templates', ['id' => $customcert->templateid], '*', MUST_EXIST);
    $template = new \mod_customcert\template($template);
    $template->generate_pdf(false, $userid);
    exit();
}

$table = new \mod_customcert\my_certificates_table($userid, $download);
$table->define_baseurl($pageurl);

if ($table->is_downloading()) {
    $table->download();
    exit();
}

// Additional page setup.
$PAGE->navbar->add(get_string('profile'), new moodle_url('/user/profile.php', ['id' => $userid]));
$PAGE->navbar->add(get_string('mycertificates', 'customcert'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mycertificates', 'customcert'));
echo html_writer::div(get_string('mycertificatesdescription', 'customcert'));
$table->out($perpage, false);
echo $OUTPUT->footer();
