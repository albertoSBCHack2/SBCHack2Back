<?php
    class PadrinosDomain extends BaseDomain {

        //Método para obtener las padrinos de los ahorradores.
        public function obtener( $params ) {
            return $this->getModel('usuarios', 'padrinos-ahijados')->obtenerPadrinos( $params );
        }

        //Método para agregar padrinos.
        public function agregar( $params ) {
            return $this->getModel('usuarios', 'padrinos-ahijados')->agregar([
                'id_usuario_padrino' => $params['idUsuarioPadrino'],
                'id_usuario_ahijado' => $params['idUsuarioAhijado']
            ]);
        }
    }
?>
