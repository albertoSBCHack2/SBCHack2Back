<?php
    class PadrinosAhijadosModel extends BaseModel {
        //Método para obtener los padrinos.
        public function obtenerPadrinos( $params ) {
            return $this->connection->exec('padrinos_ahijados_obtener_padrinos', $params);
        }

        //Método para obtener los ahijados.
        public function obtenerAhijados( $params ) {
            return $this->connection->exec('padrinos_ahijados_obtener_ahijados', $params);
        }

        //Método para agregar padrinos y/o ahijados.
        public function agregar( $params ) {
        $this->connection->insert('padrinos_ahijados', [
            '_insert' => $params
        ]);
    }
    }
?>
