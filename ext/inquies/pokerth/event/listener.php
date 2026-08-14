<?php

namespace inquies\pokerth\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    protected $db;
    protected $request;
    protected $template;
    protected $phpbb_root_path;
    protected $user;

    protected $dbname;

   /**
    * Constructor
    *
    * @param \phpbb\db\driver\driver_interface      $db             Database object
    * @param \phpbb\request\request                 $request        Request object
    * @param \phpbb\template\template               $template       Template object
    * @param string                                 $phpbb_root_path Path to phpBB root
    * @param \phpbb\user                            $user           User object
    * @access public
    */
    public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\template\template $template, $phpbb_root_path, \phpbb\user $user)
    {
        $this->request = $request;
        $this->db = $db;
        $this->template = $template;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->user = $user;
        $this->dbname = $this->request->server('HTTP_HOST') == "test.pokerth.net" ? "pokerth_ranking_test" : "pokerth_ranking";
    }

    /**
     * Assign functions defined in this class to event listeners in the core
     *
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return [
            'core.ucp_activate_after' => 'afterActivation',
            'core.ucp_register_data_after' => 'afterReg',
            'core.ucp_profile_reg_details_validate' => 'validateRegDetails',
            'core.ucp_profile_reg_details_sql_ary' => 'afterRegDetails',
            'core.permissions' => 'add_permission',
            'core.page_header' => 'add_pth_assets'
        ];
    }

	// Add administrative permissions to allow post deletion
	public function add_permission($event)
	{
		$permissions = $event['permissions'];
		$permissions['u_delete_my_account_posts'] = array('lang' => 'ACL_U_DELETE_MY_ACCOUNT_POSTS', 'cat' => 'profile');
		$event['permissions'] = $permissions;
	}

    /**
     * Cache-Busting für PTH Vue.js Assets
     * Liest das Laravel Mix Manifest und setzt Template-Variablen mit versionierten URLs
     *
     * @param \phpbb\event\data $event The event object
     */
    public function add_pth_assets($event)
    {
        // Vite erzeugt stabile Dateinamen – kein Manifest nötig.
        // Cache-Busting via Dateizeitstempel.
        $base = $this->phpbb_root_path . 'pthranking/public';

        $vars = [
            'PTH_CSS_URL'        => $this->get_asset_url('/css/pth.css', $base),
            'PTH_JS_URL'         => $this->get_asset_url('/js/pth.js', $base),
            'PTH_INJECTIONS_URL' => $this->get_asset_url('/js/injections.js', $base),
        ];

        // Spectool CSS + JS nur auf der Spectool-Seite in den <head> laden
        $request_uri = $this->request->server('REQUEST_URI');
        if (strpos($request_uri, '/spectool') !== false)
        {
            $vars['PTH_SPECTOOL_CSS_URL'] = $this->get_asset_url('/css/spectool.css', $base);
            $vars['PTH_SPECTOOL_JS_URL']  = $this->get_asset_url('/js/spectool.js', $base);
        }

        $this->template->assign_vars($vars);
    }

    /**
     * Holt die versionierte URL für ein Asset
     *
     * @param string $path Der relative Pfad zum Asset (z.B. '/js/pth.js')
     * @param array $manifest Das Mix-Manifest Array
     * @return string Die versionierte URL
     */
    private function get_asset_url($path, $base)
    {
        $file_path = $base . $path;
        if (file_exists($file_path))
        {
            return '/pthranking' . $path . '?v=' . filemtime($file_path);
        }
        return '/pthranking' . $path;
    }

    /**
     *
     * @param \phpbb\event\data $event The event object
     */
    public function afterActivation($event)
    {
        $sql = 'SELECT `player_id` FROM `'.$this->dbname.'`.`player`
            WHERE username = \''.$event['user_row']['username'].'\';';
        $result = $this->db->sql_query($sql);
        $player = $this->db->sql_fetchrow($result);
        if(!$player) {
            // @TODO: What todo if player not found? so far this event is only triggered by success = player always found.
            return;
        }
        $this->db->sql_freeresult($result);

        $sql = 'UPDATE `'.$this->dbname.'`.`player`
            set active = 1
            WHERE player_id = \''.$player['player_id'].'\'';
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);

         $sql = '
            INSERT INTO `'.$this->dbname.'`.`player_ranking` (`player_id`, `username`, `final_score`, `points_sum`, `season_games`, `average_score`)
            VALUES(
                '.$player['player_id'].',
                \''.$event['user_row']['username'].'\',
                0,
                0,
                0,
                0
            )
            ON DUPLICATE KEY UPDATE final_score = 0, points_sum = 0, season_games = 0, average_score = 0;    
            ;
        ';
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);       

        // file_put_contents("/var/www/pokerth_test/pth_helper.log", "afterActivation sql=" . $sql . "\n", FILE_APPEND);
    }

    /**
     *
     * @param \phpbb\event\data $event The event object
     */
    public function afterReg($event)
    {
        $username = $event['data']['username'];
        $email = $event['data']['email'];

        // $username = "sp0ckss";
        // $email = "dummy36@pokerth.netss";

        $sql = 'SELECT *
            FROM `'.$this->dbname.'`.`player`
            WHERE email = \''.$email.'\'
            OR username = \''.$username.'\'';
        $result = $this->db->sql_query($sql);
        if($this->db->sql_fetchrow($result)) {
            $event['error'] = ["The email address and/or username is already used in the ranking db - please contact a forum admin."];
            $this->db->sql_freeresult($result);
            return;
        }
        $this->db->sql_freeresult($result);

        $sql = 'SELECT *
            FROM `'.$this->dbname.'`.`suspended_nicknames`
            WHERE nickname = \''.$username.'\'';
        $result = $this->db->sql_query($sql);
        if($this->db->sql_fetchrow($result)) {
            $event['error'] = ["The username is suspended until next season."];
            $this->db->sql_freeresult($result);
            return;
        }
        $this->db->sql_freeresult($result);

        if(is_array($event['error']) && count($event['error']) > 0) return;

        // Restrict password characters to allowed set for new registrations
        if (!preg_match('/^[A-Za-z0-9.,_-]+$/', $event['data']['new_password']))
        {
            $event['error'] = ["Password contains invalid characters. Only letters, numbers and the characters . , _ - are allowed."];
            return;
        }

        $sql = '
            INSERT INTO `'.$this->dbname.'`.`player` (`username`, `password`, `email`, `created`, `blocked`, `active`)
            VALUES(
                \''.$username.'\',
                AES_ENCRYPT(\''.$event['data']['new_password'].'\', \''.APP_SALT.'\'),
                \''.$email.'\',
                \''.date("Y-m-d H:i:s").'\',
                0,
                0
            );
        ';
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);

        // file_put_contents("/var/www/pokerth_test/pth_helper.log", "afterReg=data: " . $sql . "\n", FILE_APPEND);
    }

    /**
     * Restrict password characters to the set the game client supports
     * when the password is changed via UCP -> Profile -> Edit account settings.
     *
     * @param \phpbb\event\data $event The event object
     */
    public function validateRegDetails($event)
    {
        $data = $event['data'];

        if (empty($data['new_password'])) return;

        if (!preg_match('/^[A-Za-z0-9.,_-]+$/', $data['new_password']))
        {
            $error = $event['error'];
            $error[] = "Password contains invalid characters. Only letters, numbers and the characters . , _ - are allowed.";
            $event['error'] = $error;
        }
    }

    /**
     * Keep the ranking db password in sync when it is changed via
     * UCP -> Profile -> Edit account settings. Without this the new password
     * only works on the website, but not in the game clients.
     *
     * @param \phpbb\event\data $event The event object
     */
    public function afterRegDetails($event)
    {
        $data = $event['data'];

        if (empty($data['new_password'])) return;

        // The phpBB users table is only updated after this event, so
        // $this->user->data still holds the username the player row is keyed on.
        $username = $this->db->sql_escape($this->user->data['username']);
        $password = $this->db->sql_escape($data['new_password']);

        $sql = 'UPDATE `'.$this->dbname.'`.`player`
            SET `password` = AES_ENCRYPT(\''.$password.'\', \''.APP_SALT.'\')
            WHERE `username` = \''.$username.'\'';
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
    }
}