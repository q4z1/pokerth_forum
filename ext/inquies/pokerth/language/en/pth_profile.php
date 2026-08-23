<?php
/**
 * Country- und Gender-Feld im UCP-Profil sowie die Flaggen an den Beiträgen.
 *
 * Die Texte standen vorher fest verdrahtet in injections.js.
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'PTH_COUNTRY'			=> 'Country',
	'PTH_COUNTRY_SELECT'	=> 'Select country',
	'PTH_GENDER'			=> 'Gender',
	'PTH_GENDER_MALE'		=> 'male',
	'PTH_GENDER_FEMALE'		=> 'female',
	'PTH_NONE'				=> 'none',

	'PTH_INVALID_COUNTRY'	=> 'The selected country is not valid.',
	'PTH_INVALID_GENDER'	=> 'The selected gender is not valid.',
]);
