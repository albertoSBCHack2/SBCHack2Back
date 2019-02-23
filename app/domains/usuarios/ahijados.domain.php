<?php
    class AhijadosDomain extends BaseDomain {
        //Método para agregar ahijados.
        public function agregar( $params ) {
            return $this->getModel('usuarios', 'padrinos-ahijados')->agregar([
                'id_usuario_padrino' => $params['idUsuarioPadrino'],
                'id_usuario_ahijado' => $params['idUsuarioAhijado']
            ]);
        }
    }
?>