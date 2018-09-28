<?php

namespace Models;

class Agency {

    public function getAgencies() {

    }

    public function setAgency($id) {

        $agency = get_option('melhorenvio_agency_jadlog_v2');
        if (!$agency) {
            add_option('melhorenvio_agency_jadlog_v2', $id);
            return [
                'success' => true,
                'id' => $id
            ];
        }

        update_option('melhorenvio_agency_jadlog_v2', $id);
        return [
            'success' => true,
            'id' => $id
        ];
    }
}