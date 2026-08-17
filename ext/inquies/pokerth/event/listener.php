<?php

namespace inquies\pokerth\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class listener implements EventSubscriberInterface
{
    protected $db;
    protected $request;
    protected $template;
    protected $phpbb_root_path;
    protected $user;
    protected $controller_helper;
    protected $symfony_request;
    protected $pages_table;

    protected $dbname;

   /**
    * Constructor
    *
    * @param \phpbb\db\driver\driver_interface      $db             Database object
    * @param \phpbb\request\request                 $request        Request object
    * @param \phpbb\template\template               $template       Template object
    * @param string                                 $phpbb_root_path Path to phpBB root
    * @param \phpbb\user                            $user           User object
    * @param \phpbb\controller\helper               $controller_helper Controller helper, baut Routen-URLs
    * @param \phpbb\symfony_request                 $symfony_request Symfony-Request, liefert die aktive Route
    * @param string                                 $table_prefix   Tabellenpräfix des Boards
    * @access public
    */
    public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\template\template $template, $phpbb_root_path, \phpbb\user $user, \phpbb\controller\helper $controller_helper, \phpbb\symfony_request $symfony_request, $table_prefix)
    {
        $this->request = $request;
        $this->db = $db;
        $this->template = $template;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->user = $user;
        $this->controller_helper = $controller_helper;
        $this->symfony_request = $symfony_request;
        // Nicht %phpbb.pages.tables.pages%, damit der Container auch dann noch
        // baut, wenn die Pages-Erweiterung einmal deaktiviert wird.
        $this->pages_table = $table_prefix . 'pages';
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
            'core.ucp_profile_reg_details_data' => 'preventEmailChange',
            'core.ucp_profile_reg_details_validate' => 'validateRegDetails',
            'core.ucp_profile_reg_details_sql_ary' => 'afterRegDetails',
            'core.permissions' => 'add_permission',
            'core.user_setup' => 'drop_url_sid',
            'core.page_header' => [
                ['add_pth_assets'],
                ['lock_email_field'],
                ['set_pages_canonical'],
            ]
        ];
    }

    /**
     * Setzt ein Canonical für die Seiten der Pages-Erweiterung.
     *
     * Dieselbe Seite ist über vier URLs erreichbar: die eigentliche Route
     * (/app.php/leaderboard), die Legacy-Route aus Pages 1.0
     * (/app.php/page/leaderboard) und beide noch einmal ohne app.php, weil
     * nginx dorthin umschreibt. Alle vier antworten mit 200 und ohne
     * Canonical – die Google Search Console meldet sie entsprechend als
     * "Duplikat – vom Nutzer nicht als kanonisch festgelegt".
     *
     * viewtopic.php und Co. setzen U_CANONICAL selbst, die Erweiterung tut es
     * nicht. Der Style rendert die Variable bereits, sobald sie gefüllt ist.
     *
     * Kanonisch ist die dynamische Route. controller.helper baut sie in der
     * Form, die auch intern verlinkt wird – mit app.php, solange im ACP kein
     * URL-Rewriting aktiv ist, und ohne, sobald es eingeschaltet wird.
     */
    public function set_pages_canonical()
    {
        $route = $this->symfony_request->attributes->get('_route');

        if ($route === 'phpbb_pages_main_controller')
        {
            // Legacy-Route /page/{route}: auf die dynamische Route derselben Seite zeigen.
            $page_route = $this->symfony_request->attributes->get('route');
            $sql = 'SELECT page_id FROM ' . $this->pages_table . "
                WHERE page_route = '" . $this->db->sql_escape((string) $page_route) . "'";
            $result = $this->db->sql_query($sql);
            $page_id = $this->db->sql_fetchfield('page_id');
            $this->db->sql_freeresult($result);

            if ($page_id === false)
            {
                return;
            }

            $route = 'phpbb_pages_dynamic_route_' . (int) $page_id;
        }
        else if (strpos((string) $route, 'phpbb_pages_dynamic_route_') !== 0)
        {
            return;
        }

        $this->template->assign_var('U_CANONICAL', $this->controller_helper->route(
            $route, [], false, false, UrlGeneratorInterface::ABSOLUTE_URL
        ));
    }

    /**
     * Hält die Session-ID im Cookie statt in jeder URL.
     *
     * phpBB hängt die sid nur dann an alle Links, wenn der Client kein Cookie
     * schickt (phpbb/session.php:266). Ein Crawler, der Cookies ignoriert,
     * bekommt dadurch mit jeder Antwort eine neue sid — und damit werden aus
     * den immer gleichen 102 Links der Startseite 102 URLs, die er noch nie
     * gesehen hat. Er stellt sie in die Queue, holt sie, bekommt wieder eine
     * neue sid, und geht nie aus: Was monatelang wie ein Angriff aussah, war
     * ein Crawler, der nicht terminieren konnte.
     *
     * Für die Bots aus seiner eigenen Liste unterdrückt phpBB das längst
     * (phpbb/session.php:682), es erkennt nur keinen Crawler, der einen
     * Browser-User-Agent fälscht. Hier gilt dieselbe Regel für jeden Client
     * ohne Cookies.
     *
     * Besucher mit Cookies laufen gar nicht erst in diesen Zweig, deren Links
     * waren nie betroffen.
     *
     * @param \phpbb\event\data $event The event object
     */
    public function drop_url_sid($event)
    {
        // Das ACP baut die sid bewusst in seine Links ein, das bleibt so.
        // adm/index.php definiert die Konstante vor dem require der common.php.
        if (defined('NEED_SID'))
        {
            return;
        }

        global $SID, $_SID;

        // Genau das Paar, das phpBB selbst für einen erkannten Bot setzt.
        // append_sid() nimmt bei leerem $_SID seine Abkürzung und hängt nichts an.
        $SID = '?sid=';
        $_SID = '';
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

        // Auf klassischen phpBB-Seiten steuert das Vue-Bundle nur die
        // Seitenleiste bei – dort darf es hinter dem load-Event nachkommen,
        // damit es forum_fn.js nicht den Main-Thread wegnimmt. Auf den
        // Controller-Routen der Pages-Erweiterung ist es der Seiteninhalt
        // selbst und muss sofort laden.
        $route = (string) $this->symfony_request->attributes->get('_route');
        $is_app_page = $route !== '' && strpos($route, 'phpbb_pages_') === 0;

        if (!$is_app_page)
        {
            $vars['PTH_JS_DEFER'] = true;
            $vars['PTH_JS_URL_JSON'] = "'" . $vars['PTH_JS_URL'] . "','" . $vars['PTH_INJECTIONS_URL'] . "'";
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
     * The email address exists in both the phpBB and the ranking db, so it
     * must not be changed through the forum alone. Discard whatever was
     * submitted and keep the address the account already has.
     *
     * @param \phpbb\event\data $event The event object
     */
    public function preventEmailChange($event)
    {
        $data = $event['data'];
        $data['email'] = $this->user->data['user_email'];
        $event['data'] = $data;
    }

    /**
     * Render the email address as plain text instead of an input field.
     * Runs on core.page_header, which fires after the UCP module has assigned
     * its own template variables, so this assignment wins.
     *
     * @param \phpbb\event\data $event The event object
     */
    public function lock_email_field($event)
    {
        $this->template->assign_var('S_CHANGE_EMAIL', false);
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