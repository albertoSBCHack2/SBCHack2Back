<?php
    class CuentasModel extends BaseModel {
         //Método para asociar cuentas.
        public function agregar( $params ) {
            return $this->connection->insert('cuentas:id_cuenta', [
                '_insert' => $params
            ]);
        }
    }
?>

