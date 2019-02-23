<?php
    require_once _APP . '/components/banregio-api.component.php';

    class BanregioDomain extends BaseDomain {
        public function guardarToken( $params ) {
          $this->getModel('banregio', 'banregio-token')->guardar( $params );
        }

        public function obtenerToken( $params = [] ) {
          return $this->getModel('banregio', 'banregio-token')->obtener($params)[0] ?? null;
        }
    }
?>
