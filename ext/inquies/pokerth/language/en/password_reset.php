<?php

/**
 * SEO Metadata extension for phpBB.
 * @author Alfredo Ramos <alfredo.ramos@yandex.com>
 * @copyright 2018 Alfredo Ramos
 * @license GNU GPL-2.0-only
 */

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
 * @ignore
 */
if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'NO_RESET_TOKEN'			=> 'You did not provide a password reset token.',
    'NO_USER'					=> 'The requested user does not exist.',
	'FORM_INVALID'			=> 'The submitted form was invalid. Try submitting again.',
	'PASSWORD_RESET'			=> 'Your password has been successfully reset.',
	'RESET_PASSWORD'					=> 'Reset password',
	'RESET_TOKEN_EXPIRED_OR_INVALID'	=> 'The password reset token you supplied is invalid or has expired.',
	'NEW_PASSWORD'					=> 'New password',
	'NEW_PASSWORD_CONFIRM_EMPTY'	=> 'You did not enter a confirm password.',
	'NEW_PASSWORD_ERROR'			=> 'The passwords you entered do not match.',
	'CONFIRM_PASSWORD'			=> 'Confirm password',
	'CONFIRM_PASSWORD_EXPLAIN'	=> 'You only need to confirm your password if you changed it above.',
	'PASS_TYPE_ALPHA_EXPLAIN'	=> 'Password must be at least %1$s long, must contain letters in mixed case and must contain numbers.',
	'PASS_TYPE_ANY_EXPLAIN'		=> 'Must be at least %1$s long.',
	'PASS_TYPE_CASE_EXPLAIN'	=> 'Password must be at least %1$s long and must contain letters in mixed case.',
	'PASS_TYPE_SYMBOL_EXPLAIN'	=> 'Password must be at least %1$s long, must contain letters in mixed case, must contain numbers and must contain symbols.',
	'PASSWORD'					=> 'Password'
]);
