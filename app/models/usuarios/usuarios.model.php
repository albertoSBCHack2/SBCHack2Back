<?php
    class UsuariosModel extends BaseModel {
        //Método para obtener al usuario.
        public function getUser( $params ) {
            if( isset( $params['contrasena'] ) ) {
                $params['contrasena'] = hash( 'sha256', $params['contrasena'] ); 
            }
 
            return $this->connection
                ->select('id_usuario, id_rol, nombre, celular')
                ->from('usuarios')
                ->where('nom_usuario')->eq('nomUsuario')
                ->_and('contrasena')->eq('contrasena')
                ->exec($params) ;
        }

        //Método para obtener las cuentas de los usuarios.
        public function getAccounts( $params ) {
            if( isset( $params['contrasena'] ) ) {
                $params['contrasena'] = hash( 'sha256', $params['contrasena'] ); 
            }
 
            return $this->connection
                ->select('id_banco, num_cuenta')
                ->from('cuentas')
                ->where('id_usuario')->eq('idUsuario')
                ->exec($params) ;
        }
    }
?>

