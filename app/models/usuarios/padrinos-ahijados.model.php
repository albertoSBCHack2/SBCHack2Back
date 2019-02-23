<?php
    class PadrinosAhijadosModel extends BaseModel {


        //Método para obtener las cuentas de los usuarios.
        public function obtener( $params ) {
            return $this->connection->exec('padrinos_ahijados_obtener', $params);
        }
    }
?>
