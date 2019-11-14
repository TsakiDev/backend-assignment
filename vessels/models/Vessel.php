<?php
     
     class Vessel
     {
          //DB stuff
          private $conn;
          private $table = 'vessels';

          private $sql;

          public function __construct($db) {
               $this->conn = $db;
          }

          public function read_all()
          {
               $query = 'SELECT 
               STATION_ID,
               STATUS,
               MMSI,
               SPEED,
               LAT,
               LON,
               COURSE,
               HEADING,
               ROT,
               TIMESTAMP
               FROM
               '.$this->table;

               //Prepare statement
               $stmt = $this->conn->prepare($query);

               //Execute query
               $stmt->execute();

               return $stmt;
          }

          //Return filtered data
          public function read_filtered()
          {
               unset($sql);
               
               if (isset($this->mmsis)) {
                    $sql[] = " MMSI IN ($this->mmsis) ";
               }
               
               if (isset($this->minLat)) {
                    $sql[] = " LAT >= '$this->minLat' ";
               }

               if (isset($this->maxLat)) {
                    $sql[] = " LAT <= '$this->maxLat' ";
               }

               if (isset($this->minLon)) {
                    $sql[] = " LON >= '$this->minLon' ";
               }

               if (isset($this->maxLon)) {
                    $sql[] = " LON <= '$this->maxLon' ";
               }

               if (isset($this->startDatetime)) {
                    $sql[] = " TIMESTAMP >= '$this->startDatetime' ";
               }

               if (isset($this->endDatetime)) {
                    $sql[] = " TIMESTAMP <= '$this->endDatetime' ";
               }
               
               $query = "SELECT STATION_ID, STATUS, MMSI, SPEED, LAT, LON, COURSE, HEADING, ROT, TIMESTAMP
                         FROM $this->table";
               
               if (!empty($sql)) {
                    $query .= ' WHERE ' . implode(' AND ', $sql);
               }

               //Prepare statement
               $stmt = $this->conn->prepare($query);

               try {
                    //Execute query
                    $stmt->execute();
               } catch (PDOException $e) {
                    echo 'Connection Error: ' . $e->getMessage();
               }

               return $stmt;
          }
     }
?>