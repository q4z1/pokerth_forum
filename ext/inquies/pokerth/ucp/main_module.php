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

// This class processes the deletion request
class main_module
{
	protected $auth;

	protected $request;

	protected $template;

	protected $user;

	public $u_action;

	// Main function
	function main($id, $mode)
	{
		global $auth, $user, $template, $request;

		$this->auth = $auth;
		$this->user = $user;
		$this->template = $template;
		$this->request = $request;

		// If the user OK'd the confirmation box then delete them
		if (confirm_box(true))
		{
			$this->delete_account();
		}
		else
		{
			$submit	= $this->request->variable('submit', false);

			// If the user submitted a delete request then check it
			if ($submit)
			{
				$delete_type = $this->delete_confirmation();

				// If we are still here the request is valid so display the final confirmation box
				confirm_box(false, 'DELETE_FINAL', build_hidden_fields(
					array(
						'submit' => false,
						'delete_type' => $delete_type
					)
				));
			}
		}
		// Otherwise just display the template
		$this->template->assign_vars(array(
			'S_CAN_DELPOSTS' => $this->auth->acl_get('u_delete_my_account_posts'),
			'S_UCP_ACTION' => $this->u_action,
		));

		add_form_key('ucp_delete_my_account');

		$this->tpl_name = 'ucp_delete_my_account';

		$this->page_title = 'DELETE_MY_ACCOUNT';
	}

	// Confirm everything before the final check
	private function delete_confirmation()
	{
		global $phpbb_container;

		$passwords_manager = $phpbb_container->get('passwords.manager');

		$delete       = $this->request->variable('delete', false);
		$understand   = $this->request->variable('understand', false);
		$password     = $this->request->variable('password', '', true);
		$remove_posts = $this->request->variable('remove_posts', false);

		// First check that the form key is valid
		if (!check_form_key('ucp_delete_my_account'))
		{
			trigger_error('<p>' . $this->user->lang['BAD_FORM_KEY_ERROR'] . '</p><p>' . sprintf($this->user->lang['RETURN_UCP'], '<a href="' . $this->u_action . '">', '</a></p>'), E_USER_WARNING);
		}

		// Decide if the posts are to be deleted or not
		$delete_type = $this->auth->acl_get('u_delete_my_account_posts') ? ($remove_posts ? 'remove' : 'retain') : 'retain';

		// Stop Founders from deleting themselves
		if ($this->user->data['user_type'] == USER_FOUNDER)
		{
			trigger_error('<p>' . $this->user->lang['FOUNDER_ERROR'] . '</p><p>' . sprintf($this->user->lang['RETURN_UCP'], '<a href="' . $this->u_action . '">', '</a></p>'), E_USER_WARNING);
		}

		// Check the user really wants to delete themselves
		if (!$delete)
		{
			trigger_error('<p>' . $this->user->lang['REALLY_ERROR'] . '</p><p>' . sprintf($this->user->lang['RETURN_UCP'], '<a href="' . $this->u_action . '">', '</a></p>'), E_USER_WARNING);
		}

		// Check the user understands that it can not be undone
		if (!$understand)
		{
			trigger_error('<p>' . $this->user->lang['UNDERSTAND_ERROR'] . '</p><p>' . sprintf($this->user->lang['RETURN_UCP'], '<a href="' . $this->u_action . '">', '</a></p>'), E_USER_WARNING);
		}

		// Check that the user entered their password correctly
		if (!$passwords_manager->check($password, $this->user->data['user_password']))
		{
			trigger_error('<p>' . $this->user->lang['PASSWORD_ERROR'] . '</p><p>' . sprintf($this->user->lang['RETURN_UCP'], '<a href="' . $this->u_action . '">', '</a></p>'), E_USER_WARNING);
		}

		return $delete_type;
	}

	// Delete the account
	private function delete_account()
	{
		global $db, $phpbb_log, $phpbb_root_path, $request;

		$delete_type = $this->request->variable('delete_type', 'retain');

		// Create the log message
		$log = sprintf($this->user->lang['LOG_USER_DELETED'], $this->user->data['username'], $this->user->data['user_id']);

		$dbname = $request->server('HTTP_HOST') == "test.pokerth.net" ? "pokerth_ranking_test" : "pokerth_ranking";

		// Append the log message with a note about what happened to the posts
		// if ($this->user->data['user_posts'] > 0)
		// {
		// 	$delete_type == 'retain' ? $log .= $this->user->lang['LOG_POST_RETAINED'] : $log .= $this->user->lang['LOG_POST_REMOVED'];
		// }
		// else
		// {
		// 	$log .= $this->user->lang['LOG_NO_POSTS'];
		// }

		// Add the deletion to the user log using ANONYMOUS user_id (we have to do this before the delete otherwise it won't work)
		$phpbb_log->add('user', ANONYMOUS, $this->user->data['user_ip'], 'LOG_USER_DELETED', time(), array('reportee_id' => ANONYMOUS, 'log_data' => $log));

		// Change the username so that it will be unique
		$new_name = sprintf($this->user->lang['DELETED_USER'], $this->user->data['user_id']);

		// Update the various places it might be set outside of the user table
		user_update_name($this->user->data['username'], $new_name);

		// Update the user table but only the username column (not username-clean) because we'll delete the user next anyway
		$db->sql_query('UPDATE ' . USERS_TABLE . " SET username='" . $db->sql_escape($new_name) . "' WHERE user_id=" . intval($this->user->data['user_id']));

		// Delete the user (and the posts if requested otherwise we keep them with the new username)
		user_delete($delete_type, $this->user->data['user_id'], true);

        $sql = 'SELECT `player_id` FROM `'.$dbname.'`.`player`
            WHERE username = \''.$this->user->data['username'].'\';';
        $result = $db->sql_query($sql);
        $player = $db->sql_fetchrow($result);
        if($player) {
			$sql = 'UPDATE `'.$dbname.'`.`player`
				set active = 0, username = \'deleted_' . $player['player_id'] . '\', email = \'deleted_' . $player['player_id'] . '\'
				WHERE username = \''.$this->user->data['username'].'\'';
			$result = $db->sql_query($sql);
			$db->sql_freeresult($result);
        }
        $db->sql_freeresult($result);

        $sql = 'SELECT `player_id` FROM `'.$dbname.'`.`player_ranking`
            WHERE username = \''.$this->user->data['username'].'\';';
        $result = $db->sql_query($sql);
        $player_ranking = $db->sql_fetchrow($result);
        if($player_ranking) {
			$sql = 'UPDATE `'.$dbname.'`.`player_ranking`
				set username = \'deleted_' . $player_ranking['player_id'] . '\'
				WHERE username = \''.$this->user->data['username'].'\'';
			$result = $db->sql_query($sql);
			$db->sql_freeresult($result);
        }
        $db->sql_freeresult($result);

		if($player || $player_ranking){
			$player_id = $player ? $player['player_id'] : $player_ranking['player_id'];
			$sql = '
				INSERT INTO `'.$dbname.'`.`suspended_nicknames` (`player_id`, `nickname`)
				VALUES(
					'.$player_id.',
					\''.$this->user->data['username'].'\'
				);
			';
			$result = $db->sql_query($sql);
			$db->sql_freeresult($result);  
		}

		// Say goodbye to the user and redirect them to the board root
		meta_refresh(6, $phpbb_root_path);
		trigger_error($this->user->lang['GOODBYE_ERROR']);
	}
}
