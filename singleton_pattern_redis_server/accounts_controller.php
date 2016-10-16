<?php

class AccountsController extends AppController {

	public $name = 'Accounts';

	public $uses = array(
		'Account',
		'AccountType',
		'Twitter', 
		'Target', 
		'Experience',
        'HotList',
        'Api'
	);

	public $paginate = array('limit' => 10);
	
	public $helpers = array(
		'Paginator'
	);
	
	/* remove redis key */
    function deleteRedisChidren(){
        $masterid=$this->Session->read('master_id');
        //$this->connectRedis();
        $this->redis = SiteController::connectRedis();
        $this->redis->del("masters_children_".$masterid);
        Cakelog::write("debug", "redis master children removed...");
    }
    
    /* push child records in redis key if master has children */
    function checkChildren($masterid){        
        $this->Account->recursive = 0;
        $children_accounts=$this->Account->find('all',array(
            'conditions'=>array(
                'Account.master_id'=>$masterid
            )
        ));
        Cakelog::write("debug", "masterid : ". $masterid );
        Cakelog::write("debug", "children ".print_r($children_accounts, true). " total children ". count($children_accounts) );
        if ( count($children_accounts) > 0 ) {
            //$this->connectRedis();
            $this->redis = SiteController::connectRedis();
            //remove if key exists
            $isthere=$this->redis->exists("masters_children_".$masterid);
            if ( $isthere == "1" ) {
                $this->redis->del("masters_children_".$masterid);          
            }
            
            //$allchs="";
            foreach ( $children_accounts as $eachchild ) {
                    //$allchs .= $eachchild['Account']['id'].",";
                    if ( $masterid == $eachchild['Account']['id'] ) {
                        $lastjarr['id'] = $eachchild['Account']['id'];
                        $lastjarr['name'] = $eachchild['Account']['name'];
                        $lastjarr['login_email'] = $eachchild['Account']['login'];
                        continue; 
                    }
                $jarr['id'] = $eachchild['Account']['id'];
                $jarr['name'] = $eachchild['Account']['name'];
                $jarr['login_email'] = $eachchild['Account']['login'];
                $this->redis->rpush("masters_children_".$masterid, json_encode($jarr));
            }
            $this->redis->lpush("masters_children_".$masterid, json_encode($lastjarr));            
            return "1";
        } else {             
            return "0";
        }
    }

	function login(){

		$this->redirect('/accounts/access');

	}

	function logout() {
        $loggedinid=$this->Session->read('account_id');
		$this->Session->delete('Account');
		$this->Session->delete('account_id');

        //NT logging out master login if any
        $ip = getenv('REMOTE_ADDR');
        $masterid=$this->Session->read('master_id');
        if ( isset($masterid) ) {
            Cakelog::write("access", "Logged OUT IP: ". $ip . " | ID : " .$masterid);
            $this->deleteRedisChidren();
            $this->Session->delete('master_id');            
        } else {
            Cakelog::write("access", "Logged OUT IP: ". $ip . " | ID : " .$loggedinid);
        }
        
        
		// We're no longer part of a fake registration
		$this->Session->delete('fake_reg');

		// We don't have an account to look up so there are no relevant videos processing
		$this->Session->delete('videos_processing');


//		$this->redirect('/');
                $strURL=Configure::read('Logout.URL');
                if($strURL==null) $strURL='/accounts/access';
                if ($this->isAdmin) $strURL='/accounts/access';
                $this->redirect($strURL);    
	}
    
	/**
	 making redis server connection and getting master account children records..
	 **/
	function dashboard($type = null) {
        $masterid = $this->Session->read('master_id');
        if ( isset($masterid)) {
            //$this->connectRedis();
            $this->redis = SiteController::connectRedis();
            $allchildren=$this->redis->lrange("masters_children_".$masterid,"0","-1");
            //echo "<pre>"; print_r( $allchildren ); echo "</pre>";
            $this->set('allchildren', $allchildren);
        }
	}
}