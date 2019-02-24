<?php
    class RetosModel extends BaseModel {
        //Método para agergar retos.
        public function agregar( $params ) {
            return $this->connection->insert('retos:id_reto', [
                '_insert' => $params
            ]);
        }

        //Método para obtener retos.
        public function obtener( $params ) {
            return $this->connection
                ->select('id_reto, vigente')
                ->from('retos')
                ->where('id_usuario_padrino_reta')->eq('idUsuarioPadrino')
                ->_and('vigente')->eq('vigente')
                ->exec($params);
        }
    }
?>