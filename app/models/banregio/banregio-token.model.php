<?php
    class banregioTokenModel extends BaseModel {
        public function guardar( $params ) {
          return $this->connection->insert('banregio_token', [
              '_insert' => $params
          ]);
        }
        public function obtener( $params ) {
          return $this->connection
              ->select('access_token, refresh_token')
              ->from('banregio_token')
              ->exec($params) ;
        }
    }
?>
