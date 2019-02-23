<?php
    class PadrinosAhijadosModel extends BaseModel {
        //Método para obtener las cuentas de los usuarios.
        public function obtener( $params ) {
            return $this->connection->exec('padrinos_ahijados_obtener', $params);
        }

         //Método para agregar padrinos y/o ahijados.
         public function agregar( $params ) {
            $this->connection->insert('padrinos_ahijados', [
                '_insert' => $params
            ]);
        }
    }
?>
