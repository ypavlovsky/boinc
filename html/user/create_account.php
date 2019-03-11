<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2008 University of California
//
// BOINC is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// BOINC is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with BOINC.  If not, see <http://www.gnu.org/licenses/>.

// RPC handler for account creation

require_once("../inc/boinc_db.inc");
require_once("../inc/util.inc");
require_once("../inc/email.inc");
require_once("../inc/xml.inc");
require_once("../inc/user_util.inc");
require_once("../inc/team.inc");
require_once("../inc/password_compat/password.inc");
require_once("../inc/consent.inc");

xml_header();

$retval = db_init_xml();
if ($retval) xml_error($retval);

$config = get_config();
if (parse_bool($config, "disable_account_creation")) {
    xml_error(ERR_ACCT_CREATION_DISABLED);
}
if (parse_bool($config, "disable_account_creation_rpc")) {
    xml_error(ERR_ACCT_CREATION_DISABLED);
}

if(defined('INVITE_CODES')) {
    $invite_code = get_str("invite_code");
    if (!preg_match(INVITE_CODES, $invite_code)) {
        xml_error(ERR_ATTACH_FAIL_INIT);
    }
} 

$email_addr = get_str("email_addr");
$email_addr = strtolower($email_addr);
$passwd_hash = get_str("passwd_hash");
$user_name = get_str("user_name");
$team_name = get_str("team_name", true);

$consent_flag = get_str("consent_flag", true);
$source = get_str("source", true);

if (!is_valid_user_name($user_name, $reason)) {
    xml_error(ERR_BAD_USER_NAME, $reason);
}

if (!is_valid_email_addr($email_addr)) {
    xml_error(ERR_BAD_EMAIL_ADDR);
}

if (is_banned_email_addr($email_addr)) {
    xml_error(ERR_BAD_EMAIL_ADDR);
}

if (strlen($passwd_hash) != 32) {
    xml_error(-1, "password hash length not 32");
}

$tmpuser = BoincUser::lookup_prev_email_addr($email_addr);
if ($tmpuser) {
    xml_error(ERR_DB_NOT_UNIQUE);
}

$user = BoincUser::lookup_email_addr($email_addr);
if ($user) {
    if ($user->passwd_hash != $passwd_hash && !password_verify($passwd_hash, $user->passwd_hash)) {
        xml_error(ERR_DB_NOT_UNIQUE);
    } else {
        $authenticator = $user->authenticator;
    }
} else {
    $user = make_user($email_addr, $user_name, $passwd_hash, 'International');
    if (!$user) {
        xml_error(ERR_DB_NOT_UNIQUE);
    }
    
    if (defined('INVITE_CODES')) {
        error_log("Account for '$email_addr' created using invitation code '$invite_code'");
    }

    // If the project has configured to use the CONSENT_TYPE_ENROLL, then
    // record it.
    list($checkct, $ctid) = check_consent_type(CONSENT_TYPE_ENROLL);
    if ($checkct and check_termsofuse()) {
        // As of Sept 2018, this code allows 'legacy' boinc clients to
        // create accounts. If consent_flag is null the code creates
        // an account as normal and there is no update to the consent
        // DB table.
        //
        // Logic:
        // * An old(er) BOINC Manager or third party GUI that doesn't
        // * support the new consent features.
        //   -> consent_flag not set (NULL).
        // * A new(er) BOINC GUI, the terms of use are shown and user
        // * agrees.
        //   -> consent_flag=1
        // * A new or older GUI, terms of use shown but the user not
        // * not agree.
        //   -> no create account RPC at all
        //
        // Projects can require explicit consent for account creation
        // by setting "account_creation_rpc_require_consent" to 1 in
        // config.xml
        //
        if ( (!is_null($consent_flag)) and $source) {
            // Record the user giving consent in database - if consent_flag is 0,
            // this is an 'anonymous account' and consent_not_required is
            // set to 1.
            if ($consent_flag==0) {
                $rc = consent_to_a_policy($user, $ctid, 0, 1, $source);
            } else  {
                $rc = consent_to_a_policy($user, $ctid, 1, 0, $source);
            }
            if (!$rc) {
                xml_error(-1, "database error, please contact site administrators");
            }
        }
        else if (parse_bool($config, "account_creation_rpc_require_consent")) {
            xml_error(ERR_ACCT_REQUIRE_CONSENT, "This project requires to consent to its terms of use. " .
                                                "Please update your BOINC software " .
                                                "or register via the project's website " .
                                                "or contact your account manager's provider.");
        }
    }

}

if ($team_name) {
    $team_name = BoincDb::escape_string($team_name);
    $team = BoincTeam::lookup("name='$team_name'");
    if ($team && $team->joinable) {
        user_join_team($team, $user);
    }
}

echo " <account_out>\n";
echo "   <authenticator>$user->authenticator</authenticator>\n";
echo "</account_out>\n";

?>
