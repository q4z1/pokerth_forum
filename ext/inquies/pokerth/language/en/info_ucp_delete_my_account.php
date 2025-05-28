<?php
/**
 *
 * Delete My Account. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017 BrokenCrust
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(

	'DELETE_MY_ACCOUNT' => 'Delete My Account',
	'DELETE_YOUR_ACCOUNT' => 'Delete Your Account',
	'DELETE_MY_ACCOUNT_TEXT' => 'You can use this form to delete your own account. Once completed all your personal and private data will be removed from this site and you will no longer be able to logon.',
	'REALY_DELETE' => 'I really wish to delete my account.',
	'UNDERSTAND_DELETE' => 'I understand that this action can not be undone.',
	'DELETE_MY_POSTS' => 'Also delete my posts (posts will become guest posts if unchecked).',
	'ENTER_PASSWORD' => 'Enter your password',
	'ENTER_PASSWORD_HERE' => 'type your password here ...',
	'DELETED_USER' => 'Deleted User %s',
	'DELETE_FINAL' => 'Final Confirmation',
	'DELETE_FINAL_CONFIRM' => '<p>Thank you for being a member of this site. We are sorry to see you go.</p><p>Your request has been validated. This is the final confirmation. Do you want to permanently delete your account?</p>',

	'LOG_USER_DELETED' => 'User %s (ID %s) has deleted their own account. ',
	'LOG_POST_REMOVED' => 'Their posts were removed.',
	'LOG_POST_RETAINED' => 'Their posts were retained.',
	'LOG_NO_POSTS' => 'They had no posts.',

	'REALLY_ERROR' => 'You must confirm that you really wish to delete your account.',
	'UNDERSTAND_ERROR' => 'You must confirm that you understand that this process can not be undone.',
	'PASSWORD_ERROR' => 'The password that you entered was not correct.',
	'FOUNDER_ERROR' => 'Founders can not use the self deletion process.',
	'BAD_FORM_KEY_ERROR' => 'The form submission could not be validated.  Please submit your request again.',
	'GOODBYE_ERROR' => 'Your account has been deleted.'

));
