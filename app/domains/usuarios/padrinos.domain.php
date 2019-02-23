<?php
    class PadrinosDomain extends BaseDomain {

        //Método para obtener las padrinos de los ahorradores.
        public function obtener( $params ) {
            return $this->getModel('usuarios', 'padrinos-ahijados')->obtener( $params );
        }
    }
?>
