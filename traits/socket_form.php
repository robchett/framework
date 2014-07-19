<?php

namespace core\traits;

trait socket_form {

    /** @var  \ElephantIO\Client */
    private $socket;

    protected function do_create_socket() {
        $this->socket = new \ElephantIO\Client('http://localhost:8000', 'socket.io', 1, false, true, true);
        $this->socket->init();
    }

    public function do_emit_message($message) {
        if (!$this->socket) {
            $this->do_create_socket();
        }
        $this->socket->emit('message', json_encode(['message' => $message, 'clientID' => $_REQUEST['data-socket']]));
    }

    protected function do_add_socket_requirements($id) {
        $this->attributes['data-ajax-socket'] = $id;
    }
} 