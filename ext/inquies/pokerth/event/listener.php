<?php

namespace inquies\pokerth\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    protected $db;
    protected $request;

    protected $dbname;

   /**
    * Constructor
    *
    * @param \phpbb\db\driver\driver_interface      $db             Database object
    * @access public
    */
    public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\request\request $request)
    {
        $this->request = $request;
        $this->db = $db;
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
            'core.ucp_register_data_after' => 'afterReg'
        ];
    }

    /**
     *
     * @param \phpbb\event\data $event The event object
     */
    public function afterActivation($event)
    {
        // file_put_contents("/var/www/pokerth_test/pth_helper.log", "afterActivation event=" . var_export($event, true) . "\n", FILE_APPEND);
        // if($event['message'] != 'ACCOUNT_ACTIVE'){
        //     file_put_contents("/var/www/pokerth_test/pth_helper.log", "afterActivation event message=" . var_export($event, true) . "\n", FILE_APPEND);
        //     return;
        // } 

        // $p = Player::selectRaw('player_id, username')
        // ->where('email', $phpbb_user->user_email)
        // ->first();
        $sql = 'SELECT `player_id` FROM `'.$this->dbname.'`.`player`
            WHERE username = \''.$event['user_row']['username'].'\';';
        $result = $this->db->sql_query($sql);
        $player = $this->db->sql_fetchrow($result);
        if(!$player) {
            // @TODO: What todo if player not found? Should be answered by ACCOUNT_ACTIVE message.
            // $event['error'] = ["The username is suspended until next season."];
            // $this->db->sql_freeresult($result);
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
}