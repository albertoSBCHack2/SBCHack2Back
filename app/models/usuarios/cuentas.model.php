<?php
    class CuentasModel extends BaseModel {
         //Método para asociar cuentas.
        public function agregar( $params ) {
            var_dump( $params ); die(); 
            return $this->connection->insert('cuentas:id_cuenta', [
                '_insert' => $params
            ]);
        }

        //Método para actualizar.
        public function actualizar( $set, $where ) {
            $this->connection->update('cuentas', [
                '_set' => $set,
                '_where' => $where
            ]);
        }
    }
?>

