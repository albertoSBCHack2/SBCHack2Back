<?php
    class PushNotificationModel extends BaseModel {
        //Método para agregar.
        public function agregar( $params ) {
            $this->connection->insert('push_notifications:id_push_notification', [
                '_insert' => $params
            ]);
        }

        //Método para obtener las push.
        public function obtener( $params ) {
            return $this->connection
                ->select('id_push_notification AS "idPushNotification", mensaje')
                ->from('push_notifications')
                ->where('id_usuario')->eq('idUsuario')
                ->_and('activa')->eq('activa')
                ->orderBy('id_push_notification')
                ->exec($params);
        }

        //Método para actualizar.
        public function actualizar( $set, $where ) {
            $this->connection->update('push_notifications', [
                '_set' => $set,
                '_where' => $where
            ]);
        }
    }
?>