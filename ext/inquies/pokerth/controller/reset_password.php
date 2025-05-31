<?php
/**
*
* This file is part of the phpBB Forum Software package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/

namespace inquies\pokerth\controller;

use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\db\driver\driver_interface;
use phpbb\event\dispatcher;
use phpbb\exception\http_exception;
use phpbb\language\language;
use phpbb\log\log_interface;
use phpbb\passwords\manager;
use phpbb\request\request_interface;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\HttpFoundation\Response;

/**
* Handling forgotten passwords via reset password functionality
*/
class reset_password
{
	/** @var array */
	protected $data;

	/* @var config */
	protected $config;

	/* @var helper */
	protected $helper;

	/** @var language */
	protected $language;

	/* @var request */
	protected $request;

	/* @var packager */
	protected $packager;

	/* @var validator */
	protected $validator;

	/* @var template */
	protected $template;

	/* @var user */
	protected $user;

	protected $db;

	/** @var array phpBB DB table names */
	protected $users_table;

	/** @var log_interface */
	protected $log;

	/** @var manager */
	protected $passwords_manager;

	protected $dispatcher;

	protected $dbname;

	/**
	 * Constructor
	 *
	 * @param config    $config
	 * @param helper    $helper
	 * @param language  $language
	 * @param request   $request
	 * @param template  $template
	 * @param user      $user
	 * @param log       $log
	 * @param db		$db
	 * @param manager 	$passwords_manager
	 * @param dispatcher $dispatcher
	 * @param string $root_path
	 * @param string $php_ext
	 * @param string $users_table
	 * 
	 */
	public function __construct(config $config, helper $helper, language $language, request_interface $request, template $template, user $user, log_interface $log, driver_interface $db, manager $passwords_manager, dispatcher $dispatcher, string $root_path, string $php_ext, string $users_table)
	{
		$this->config = $config;
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->db = $db;
		$this->request = $request;
		$this->language = $language;
		$this->log = $log;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
		$this->dispatcher = $dispatcher;
		$this->passwords_manager = $passwords_manager;
		$this->users_table = $users_table;
		$this->dbname = $this->request->server('HTTP_HOST') == "test.pokerth.net" ? "pokerth_ranking_test" : "pokerth_ranking";
	}


	/**
	 * Remove reset token for specified user
	 *
	 * @param int $user_id User ID
	 */
	protected function remove_reset_token(int $user_id)
	{
		$sql_ary = [
			'reset_token'				=> '',
			'reset_token_expiration'	=> 0,
		];

		$sql = 'UPDATE ' . $this->users_table . '
					SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
					WHERE user_id = ' . $user_id;
		$this->db->sql_query($sql);
	}

	/**
	 * Handle controller requests
	 *
	 * @return Response
	 */
	public function reset()
	{
		$submit			= $this->request->is_set_post('submit');
		$reset_token	= $this->request->variable('token', '');
		$user_id		= $this->request->variable('u', 0);

		$this->language->add_lang('password_reset', 'inquies/pokerth');

		if (empty($reset_token))
		{
			return $this->helper->message($this->language->lang('NO_RESET_TOKEN'));
		}

		if (!$user_id)
		{
			return $this->helper->message('NO_USER');
		}

		add_form_key('ucp_reset_password');

		$sql_array = [
			'SELECT'	=> 'user_id, username, user_permissions, user_email, user_jabber, user_notify_type, user_type,'
				. ' user_lang, user_inactive_reason, reset_token, reset_token_expiration',
			'FROM'		=> [$this->users_table => 'u'],
			'WHERE'		=> 'user_id = ' . $user_id,
		];

		/**
		 * Change SQL query for fetching user data
		 *
		 * @event core.ucp_reset_password_modify_select_sql
		 * @var	int	user_id		User ID from the form
		 * @var	string	reset_token Reset token
		 * @var	array	sql_array	Fully assembled SQL query with keys SELECT, FROM, WHERE
		 * @since 3.3.0-b1
		 */
		$vars = [
			'user_id',
			'username',
			'reset_token',
			'sql_array',
		];
		extract($this->dispatcher->trigger_event('core.ucp_reset_password_modify_select_sql', compact($vars)));

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query_limit($sql, 1);
		$user_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$message = $this->language->lang('RESET_TOKEN_EXPIRED_OR_INVALID') . '<br /><br />' . $this->language->lang('RETURN_INDEX', '<a href="' . append_sid("{$this->root_path}index.{$this->php_ext}") . '">', '</a>');

		if (empty($user_row))
		{
			return $this->helper->message($message);
		}

		if (!hash_equals($reset_token, $user_row['reset_token']))
		{
			return $this->helper->message($message);
		}

		if ($user_row['reset_token_expiration'] < time())
		{
			$this->remove_reset_token($user_id);

			return $this->helper->message($message);
		}

		$errors = [];

		if ($submit)
		{
			if (!check_form_key('ucp_reset_password'))
			{
				return $this->helper->message('FORM_INVALID');
			}

			if ($user_row['user_type'] == USER_IGNORE || $user_row['user_type'] == USER_INACTIVE)
			{
				return $this->helper->message($message);
			}

			// Check users permissions
			$auth = new auth();
			$auth->acl($user_row);

			if (!$auth->acl_get('u_chgpasswd'))
			{
				return $this->helper->message($message);
			}

			if (!function_exists('validate_data'))
			{
				include($this->root_path . 'includes/functions_user.' . $this->php_ext);
			}

			$data = [
				'new_password'		=> $this->request->untrimmed_variable('new_password', '', true),
				'password_confirm'	=> $this->request->untrimmed_variable('new_password_confirm', '', true),
			];
			$check_data = [
				'new_password'		=> [
					['string', false, $this->config['min_pass_chars'], 0],
					['password'],
				],
				'password_confirm'	=> ['string', true, $this->config['min_pass_chars'], 0],
			];
			$errors = array_merge($errors, validate_data($data, $check_data));
			if (strcmp($data['new_password'], $data['password_confirm']) !== 0)
			{
				$errors[] = $data['password_confirm'] ? 'NEW_PASSWORD_ERROR' : 'NEW_PASSWORD_CONFIRM_EMPTY';
			}
			if (empty($errors))
			{
				$sql_ary = [
					'user_password'				=> $this->passwords_manager->hash($data['new_password']),
					'user_login_attempts'		=> 0,
					'reset_token'				=> '',
					'reset_token_expiration'	=> 0,
				];
				$sql = 'UPDATE ' . $this->users_table . '
							SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
							WHERE user_id = ' . (int) $user_row['user_id'];
				$this->db->sql_query($sql);

				$sql = 'UPDATE `'.$this->dbname.'`.`player`
							SET `password` = AES_ENCRYPT(\''.$data['new_password'].'\', \''.APP_SALT.'\')
							WHERE `username` = \'' . $user_row['username'] . '\'';
				$this->db->sql_query($sql);

				$this->user->reset_login_keys();
				$this->log->add('user', $user_row['user_id'], $this->user->ip, 'LOG_USER_NEW_PASSWORD', false, [
					'reportee_id' => $user_row['user_id'],
					$user_row['username']
				]);
				meta_refresh(3, append_sid("{$this->root_path}index.{$this->php_ext}"));
				return $this->helper->message($this->language->lang('PASSWORD_RESET'));
			}
		}

		$this->template->assign_vars([
			'PASSWORD_RESET_ERRORS'		=> !empty($errors) ? array_map([$this->language, 'lang'], $errors) : '',
			'S_IS_PASSWORD_RESET'		=> true,
			'U_RESET_PASSWORD_ACTION'	=> $this->helper->route('phpbb_ucp_reset_password_controller'),
			'L_CHANGE_PASSWORD_EXPLAIN'	=> $this->language->lang($this->config['pass_complex'] . '_EXPLAIN', $this->language->lang('CHARACTERS', (int) $this->config['min_pass_chars'])),
			'S_HIDDEN_FIELDS'			=> build_hidden_fields([
				'u'		=> $user_id,
				'token'	=> $reset_token,
			]),
		]);

		return $this->helper->render('ucp_reset_password.html', $this->language->lang('RESET_PASSWORD'));
	}
}
