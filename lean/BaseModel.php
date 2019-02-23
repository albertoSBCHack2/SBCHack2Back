<?php
    class BaseModel{
        protected $connection;

        public function __construct( $idCnn = null ) {
            $this->connection = DBAccess::getInstance( $idCnn );
        }

        public function setConnection( $idCnn ) {
            $this->connection->setConnection( $idCnn );
        }
    }
?>