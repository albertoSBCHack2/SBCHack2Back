<?php
    class RetosModel extends BaseModel {
        //Método para agergar retos.
        public function agregar( $params ) {
            return $this->connection->insert('retos:id_reto', [
                '_insert' => $params
            ]);
        }

        //Método para obtener retos.
        public function obtenerPorPadrino( $params ) {
            return $this->connection->exec('retos_por_padrino', $params);
        }

        //Método para obtener retos.
        public function obtenerPorAhijado( $params ) {
            return $this->connection->exec('retos_por_ahijado', $params);
        }

        //Método para actualizar.
        public function actualizar( $set, $where ) {
            $this->connection->update('retos', [
                '_set' => $set,
                '_where' => $where
            ]);
        }
    }
?>