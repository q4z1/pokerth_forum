<?php
/**
 *
 * Delete My Account. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017 BrokenCrust
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace inquies\pokerth\migrations;

// Install the core data for the extention if it isn't already done
class install_extention extends \phpbb\db\migration\migration
{
	// check to see if this ext is already installed
	public function effectively_installed()
	{
		return isset($this->config['delete_my_account']);
	}

	// Add the data
	public function update_data()
	{
		return array(
			// add the confg to show the ext is installed
			array('config.add', array('delete_my_account', 1)),

			// add the permission to allow post deletion
			array('permission.add', array('u_delete_my_account_posts')),

			// add the ucp module
			array('module.add', array('ucp', 0, 'DELETE_MY_ACCOUNT')),
			array('module.add', array('ucp', 'DELETE_MY_ACCOUNT', array('module_basename' => '\inquies\pokerth\ucp\main_module', 'modes' => array('settings')))),
		);
	}
}
