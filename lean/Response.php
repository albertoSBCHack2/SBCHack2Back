<?php
    class Response {
        private $lean;
        private $success;
        private $data;
        private $error;
        private $answered;

        public function __construct( $lean ) {
            $this->lean = $lean;
            $this->success = true;

            error_clear_last();
        }

        public function setData($value) {
            $this->data = $value;
        }

        public function setError($type, $messages, $file = null, $line = null) {
            $this->success = false;
            $this->error = new ResponseError($type, $messages, $file, $line);
        }

        public function getError() {
            return $this->error ? $this->error->getErrorForResponse() : null;
        }

        public function setHttpStatus($value) {
            http_response_code($value);
        }

        public function send() {
            $response = [
                'data' => $this->success ? $this->data : null,
                'error' => $this->error ? $this->error->getErrorForResponse() : null
            ];

            if( !$this->lean->getIsRESTful() ) {
                $response['success'] = $this->success;
            }

            //Logueamos la salida.
            if( !$this->answered ) {
                $this->answered = true;
                $this->lean->getLogger()->log(3);
            }

            if( $this->lean->getIsXHR() ) {
                //ob_clean();
                //header('Content-Type: application/json; charset=utf-8');
                var_dump( $response ); //json_encode($response, JSON_UNESCAPED_UNICODE );
            }

            return $response;
        }
    }

    class ResponseError {
        private $type;
        private $messages;
        private $file;
        private $line;

        public function __construct($type, $messages, $file, $line) {
            $this->messages = $messages;
            $this->file = $file;
            $this->line = $line;
            $this->type = $type;
        }

        public function getErrorForResponse() {
            $error = [];
            $error['type'] = $this->type;
            $error['message'] = $this->messages;
            if ($this->file) $error['file'] = $this->file;
            if ($this->line) $error['line'] = $this->line;

            return $error;
        }
    }
?>
