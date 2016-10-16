<?php
class NoughtsCrosses{
    
    private $gamedata=array();   
    
    function __construct() {
        //db connect.. Singlton Pattern
        $this->objDB = DBConnect::getInstance();
    }
    
    public function get_aggregate_results(){
        echo "\nAggregate results!\n\n";
        $this->objDB->execute();
        echo "X Wins : ". $this->objDB->aggregate_result["X"]."\n";
        echo "O Wins : ". $this->objDB->aggregate_result["O"]."\n";
        echo "Draw : ". $this->objDB->aggregate_result["D"]."\n\n";
    }
    
    public function calculate_winners($gamedata){
        $gdata=""; $i=0;
        while($f = fgets($gamedata)){
            if ( trim($f) === "stop") {
                $this->get_results();
                exit;
            }
            
            if ( ord($f) == 10 ) {
                continue;
            }
            
            $gdata = $gdata . "-" . trim($f);
            $i++;
            if ( $i == 3 ) {
                $this->gamedata[] = $gdata;
                $gwinner = $this->gameLogic($gdata);
                $this->objDB->insert($gdata, $gwinner);
                $gdata=""; $i=0;
            }            
        }
    }
    
    public function gameLogic($gdata){
        $gdata=substr($gdata,1, strlen($gdata));
        $inputrows = explode("-", $gdata);
        $inputdata = array();
        foreach ( $inputrows as $row ) { 
            for( $i = 0 ; $i <= 2 ; $i ++ ) { 
                $inputdata[] = $row[$i];
            }            
        }        
        $gamestruct = array (
                    "0-1-2|0-3-6|0-4-8",
                    "1-0-2|1-4-7",
                    "2-1-0|2-4-6|2-5-8",
                    "3-0-6|3-4-5",
                    "4-1-7|4-3-5|4-0-8|4-2-6",
                    "5-4-3|5-2-8",
                    "6-7-8|6-3-0|6-4-2",
                    "7-4-1|7-6-8",
                    "8-4-0|8-5-2|8-7-6");
        $i=0;
        $status="";
        foreach ( $gamestruct as $cell) {
            $logicvalue = explode("|", $cell);
            $flag=0;
            foreach ( $logicvalue as $value ) {                
                    $logicindexs = explode("-", $value);
                    if ( $inputdata[$logicindexs[0]] == $inputdata[$logicindexs[1]] && $inputdata[$logicindexs[0]] == $inputdata[$logicindexs[2]]){
                        $status = $inputdata[$logicindexs[0]];
                        $flag=1;
                        break;
                    }
            }
        if ( $flag == 1 ) {
            break;
        }        
        $i++;            
        }
        if ( $i == 9 ) {
            $status = "D";
        }
    return $status;
    }
    
    
    public function get_results(){
        echo "Current result!\n\n";        
        echo "X Wins : ". $this->objDB->current_result["X"]."\n";
        echo "O Wins : ". $this->objDB->current_result["O"]."\n";
        echo "Draw : ". $this->objDB->current_result["D"]."\n\n";
    }
}

class DBConnect {
    public $current_result = array ("X"=>0,"O"=>0,"D"=>0);
    public $aggregate_result = array ("X"=>0,"O"=>0,"D"=>0);
    
    private static $instance = null;
    private function __construct()
    {
        $host="localhost";
        $user="root";
        $pass="";
        $db="tic-tac-toe";
        $this->conn = new mysqli($host, $user, $pass, $db);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public static function getInstance()
    {
        if (self::$instance == null)
        {
          self::$instance = new DBConnect();
        }
    return self::$instance;
    }
    
    
    function insert($gdata, $gwinner){        
        if ( $stmt = $this->conn->prepare("INSERT INTO game (data, winner) VALUES (?, ?)") ){
            $stmt->bind_param("ss", $gamedata, $winner); 
        } else {
            printf("Errormessage: %s\n", $this->conn->error);
        }
        
        $gamedata = $gdata;
        $winner = $gwinner;
        
        $stmt->execute();
        $stmt->close();
        
        $this->current_result[$gwinner]++;
        
    }
    
    function execute() {
        $sql = "SELECT winner, count(*) as winner_count FROM `game` group by winner order by winner DESC";
        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {                
                $this->aggregate_result[$row["winner"]] = $row["winner_count"];                
            }
        }
    }
    function close_connection() {
        $this->conn->close();
    }
    
}

?>