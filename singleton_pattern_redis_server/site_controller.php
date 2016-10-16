<?php

class SiteController extends Controller {

	public $uses = array(
		'Account',
		'Interview'
	);

    /**
     * This will detect the switching of SSL and make the necessary changes
     * @return bool
     */
    private function switchSSL() {
		// Make sure we stay in whatever mode we're in while in the 'advanced' section of Expose
		if (isset($this->params['prefix']) && $this->params['prefix'] == 'manage') {
			$disable_ssl = true;
		}
		// API test 
		if (isset($_GET['api_test'])) {
			return;
		}
		// Ignore switching to SSL if this is the result of a requestAction
		if (isset($this->params['requested']) && $this->params['requested'] == 1) {
			return;
		}

		// Don't be switching to SSL midway through a form post. That'd make Bad Things happen
		if (! empty($_POST)) {
			return;
		}

		// Grab the current hostname sans port
		$http_host = str_replace(':' . $_SERVER['SERVER_PORT'], '', $_SERVER['HTTP_HOST']);
		
		if (isset($this->params['url']['url'])){
			// Parse URL to a simple controller/action, regardless of actual route
			$url = Router::parse($this->params['url']['url']);
			$controller_action = $url['controller'] . '/' . $url['action'];

			// Don't switch on this action, as is it causing issues
			if ($controller_action == 'interview_responses/videos_processing') {
				return;
			}
		}

		$disable_ssl = false;

		$not_ssl = array(
			'accounts/home',
			'team_members/index',
			'jobs/index',
			'pages/display',
			'partners/index',
			'articles/index',
			'articles/view',
			'contact/index',
			// 'interview_responses/record',
			'interview_responses/view',
			'interview_responses/iframe',
			'interview_responses/review',
			'interview_responses/videos_processing',
			'interview_responses/encode',
			'short_lists/comment',
			'short_lists/edit',
			'short_lists/video',
			'documents/upload',
			'short_lists/upload'
		);

		// Assemble query params
		$query = '';
		if (count($_GET) > 1) {
			$get_params = $_GET;
			unset($get_params['url']);
			$query = '?' . http_build_query($get_params);
		}

        $this->params['url']['url'] = isset($this->params['url']['url'])? $this->params['url']['url'] : "" ;
		$url_without_transport = $http_host . $this->base . '/' . $this->params['url']['url'] . $query;

		// Trim the extra forward slashes off the URL
		$url_without_transport = trim($url_without_transport, '/');

			// Enable SSL, if disabled
			if ($_SERVER['SERVER_PORT'] == 80) {
				$this->redirect('https://' . $url_without_transport);
			}
			return false; 
	}

    /**
     * beforeFilter
     *
     * Here to run switchSSL
     */
    public function beforeFilter() {
		// Detect SSL
		
		$this->switchSSL();

		parent::beforeFilter();
	}

    /**
     * Set the standard user vars and disable old staging site.
     */
    public function beforeRender() {
		$this->_setUserViewVar();
		
		if ($_SERVER['HTTP_HOST'] == 'theneedleonline.snapshotmedia.co.uk') {
			echo 'The staging site has been disabled. Please visit http://theneedleonline.com/';
			exit();
		}
		
		parent::beforeRender();
		

	}

    /**
     * Redirect if not logged in, this is used for basic protection
     */
    protected function _redirectIfNotLoggedIn() {
		if (! $this->isLoggedin()) {
			/*$this->redirect(array(
				'controller' => 'accounts', 
				'action' => 'login'
			));*/
            $this->redirect('/accounts/login');
			return;
		}
	}

    /*
    * NT 7th July 2016 redirect TNO users to TNO login page and API users to JD page with a message...
    */	
    protected function _redirectIfDirectAccess($parentid, $targethash){
        if (! $this->isLoggedin()) {
            if ( isset($parentid) && $parentid != "" ) {
                $this->redirect('/jobs/view_invitation/'.$targethash."/rm");
            } else {
                $this->redirect('/accounts/login');   
            }            
			return;
		}
    }
    /*
	 * Disallow users who were invited to video interview from accessing most pages
	 */
	protected function _disallowInvitedUsersAccess() {
		$account_id = $this->Session->read('account_id');
		$account = $this->Account->read(null, $account_id);

		if (empty($account['Account']['email'])) {
			$this->redirect('/');
			return;
		}
	}

    /**
     * Ensure Document and Account models are set
     */
    protected function _checkModelsIsSet() {
		if (! isset($this->Account)) {
			$this->loadModel('Account');
		}
		
		if (! isset($this->Document)) {
			$this->loadModel('Document');
		}
		if (! class_exists('Sanitize')) {
			App::import('Sanitize');
		}
	}

    /**
     * Update the last accessed time on an account
     */
    protected function _updateLastAccessed() {
		
		$this->_checkModelsIsSet();
		
		$account_id = $this->Session->read("account_id");
		$timestamp = date("Y-m-d H:i:s");
		$this->Account->id = $account_id;
		$this->Account->saveField('last_accessed', $timestamp);
	}

    /**
     * @return bool
     */
    public function isLoggedIn() {
		$this->_checkModelsIsSet();

		if (! $this->Session->check('Account.id')) {
			return false;
		}
		$accountId = $this->Session->read("Account.id");

		$this->_checkVideosProcessing($accountId);

		$account = $this->Account->read(null, $accountId);
		//$this->set("current_account",$account);
		
		$fake_reg = $this->Session->read('fake_reg');
		
		if ($accountId !== NULL && $account) {
			$this->_updateLastAccessed();
			return true;
		}
		return false;
	}

    /**
     * @param $date Array   date in ['year' => '2013', 'month' => '05', 'day' => '01']
     * @return string   2013-05-01
     */
    public function changeDateFormat($date) {
		
		return $date['year'] . '-' . $date['month'] . '-' . $date['day'];
	
	}

    /**
     * @param $userType Takes a user type as a string
     * @todo  Bit of work to do here.
     */
    protected function _redirectIfNot($userType) {
		$accountId = $this->Session->read('Account.id');
		$account = $this->Account->read(null, $accountId);
		if ($account['AccountType']['short'] = 'recruiter') {
			$currentUsersType = 'employer';
		} else {
			$currentUsersType = $account['AccountType']['short'];
		}
		if ($currentUsersType !== $userType) {
			trigger_error('About to redirect for incorrect perms');
			// @TODO: Re-instate this once fully tested
			//$this->redirect('/accounts/dashboard');
		}
	}
	
	// Retrieves a google shortened URL and returns it
	public function getShortUrl($link){
		$curlObj=curl_init();
		curl_setopt($curlObj,CURLOPT_URL,'https://www.googleapis.com/urlshortener/v1/url?key=AIzaSyD6JbSQTJRknDMvTys-LyvIMbK2ywqN4U8');
		curl_setopt($curlObj,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curlObj,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curlObj,CURLOPT_HEADER,0);
		curl_setopt($curlObj,CURLOPT_HTTPHEADER,array('Content-type:application/json'));
		curl_setopt($curlObj,CURLOPT_POST,1);
		curl_setopt($curlObj,CURLOPT_POSTFIELDS,json_encode(array('longUrl'=>$link)));
		$response=curl_exec($curlObj);
		$json=json_decode($response,true);
		curl_close($curlObj);
		return $json['id'];
	}
    
    /*Creating Singleton pattern redis server instance and making sure that it is not recreated.*/
    private static $_redis_instance = null;
    
    public static function connectRedis(){        
        if(! (self::$_redis_instance instanceof Redis)) {
            self::$_redis_instance = new Redis();
            self::$_redis_instance->connect('127.0.0.1', 6379);
            self::$_redis_instance->auth("thakur123");
            Cakelog::write("debug", "Redis server singleton instance created successfully!!");
            Cakelog::write("debug", "Server is running: " .self::$_redis_instance->ping());
            
        }
        return self::$_redis_instance;         
    }
}