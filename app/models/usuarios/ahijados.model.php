<?php
    class AhijadosModel extends BaseModel {
        //Método para agregar ahijados.
        public function agregar( $params ) {
            $this->connection->insert('padrinos_ahijados', [
                '_insert' => $params
            ]);
        }
    }
?>