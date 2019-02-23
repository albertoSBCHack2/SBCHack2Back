<?php
    class AhijadosDomain extends BaseDomain {
        //Método para agregar ahijados.
        public function agregar( $params ) {
            return $this->getModel('usuarios', 'padrinos-ahijados')->agregar([
                'id_usuario_padrino' => $params['idUsuarioPadrino'],
                'id_usuario_ahijado' => $params['idUsuarioAhijado']
            ]);
        }
        //Método para obtener los ahijados del padrino enviado.
        public function obtener( $params ) {
            return $this->getModel('usuarios', 'padrinos-ahijados')->obtenerAhijados($params);
        }
    }
?>
