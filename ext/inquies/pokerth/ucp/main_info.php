<?php
/**
 *
 * Delete My Account. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017 BrokenCrust
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace inquies\pokerth\ucp;

// Define the new UCP tab and single page
class main_info
{
	function module()
	{
		return array(
			'filename' => '\inquies\pokerth\ucp\main_module',
			'title' => 'DELETE_MY_ACCOUNT',
			'modes' => array(
				'settings' => array(
					'title'	=> 'DELETE_YOUR_ACCOUNT',
					'auth' => 'ext_inquies/pokerth',
					'cat' => array('DELETE_MY_ACCOUNT'),
				),
			),
		);
	}
}
