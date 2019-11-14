<?php
    class Logger
    {
        //DB stuff
        private $conn;
        private $table = 'requests_log';

        public function __construct($db)
        {
            $this->conn = $db;
        }

        function write_log($ip)
        {
            $query = "INSERT INTO $this->table (IP) VALUES('$ip')";
            
            $stmt = $this->conn->prepare($query);

            $stmt->execute();
        }

        public function count_recent_visits($ip) {

            $query = "Select count(*) as c from $this->table where IP = '$ip' and TIMESTAMP > (NOW() - INTERVAL 1 HOUR)";

            //Prepare statement
            $stmt = $this->conn->prepare($query);

            //Execute query
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row['c'];
        }
    }
?>