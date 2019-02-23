<?php
    class UsuariosModel extends BaseModel {
        //Método para obtener al usuario.
        public function obtener( $params ) {
            if( isset( $params['contrasena'] ) ) {
                $params['contrasena'] = hash( 'sha256', $params['contrasena'] ); 
            }

            return $this->connection
                ->select('id_usuario, nombre, celular')
                ->from('usuarios')
                ->where('nom_usuario')->eq('nomUsuario')
                ->_and('contrasena')->eq('contrasena')
                ->exec($params) ;
        }
    }
?>

