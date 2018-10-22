<?php

namespace Controllers;

class LogsController {

    public function index() {

        global $wpdb;
        $sql = sprintf('SELECT * FROM %spostmeta WHERE meta_key = "logs_melhorenvio" order by meta_id desc limit 1000', $wpdb->prefix);
        $results = $wpdb->get_results($sql);
        $rows = '';
        foreach($results as $item) {
            $data = unserialize($item->meta_value);
            $link = '/wp-admin/admin-ajax.php?action=detail_log_melhorenvio&meta_id='.$item->meta_id;
            $rows .= '<tr>
                <td>'. $item->post_id .'</td>
                <td>' . $data['message'] . '</td>
                <td>' . $data['date'] . '</td>
                <td><a target="_blank" href="' . $link . '">ver</a></td>
            </tr>';
        }  

        echo '<h1>Logs Melhor envio</h1>';
        echo '<table border="1"><tr><td>ID</td><td>Mensagem</td><td>Data</td><td>Link</td></tr>';
        echo $rows;
        echo '</table>';
        die;
    }

    public function detail()
    {
        if (!isset($_GET['meta_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Informar o ID do erro'
            ]);
            die;
        }

        global $wpdb;
        $sql = sprintf('SELECT * FROM %spostmeta WHERE meta_id = %s', $wpdb->prefix, $_GET['meta_id']);
        $row = end($wpdb->get_results($sql));

        $data = unserialize($row->meta_value);

        echo json_encode([
            'Ordem ID' => $data['order_id'],
            'data' => $data['date'],
            'Mensagem' => $data['message'],
            'Controller' => $data['class'],
            'function'  => $data['action'],
            'endpoint' => $data['endpoint'],
            'params' => json_decode($data['payload']['body']),
            'response' => $data['response']
        ]);
        die;

    }

    public function add($order_id, $msg, $payload = [], $response = [], $class = null, $action = null, $endpoint = null) {

        $log = [
            'order_id' => $order_id,
            'date' => date('Y-m-d h:i:s'),
            'message' => $msg,
            'class' => $class,
            'action' => $action,
            'endpoint' => $endpoint,
            'payload' => $payload,
            'response' => $response,
        ];

        add_post_meta($order_id, 'logs_melhorenvio', $log);
    }
}

