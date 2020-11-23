<?php

namespace Models;

class AgencyAzul
{
    const AGENCY_SELECTED = 'melhorenvio_agency_azul_v2';

    /**
     * function to get the id of agency azul selected.
     *
     * @return bool|int
     */
    public function getSelected()
    {
        $id = get_option(self::AGENCY_SELECTED, false);

        return (empty($id)) ? false : intval($id);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function setAgency($id)
    {
        delete_option(self::AGENCY_SELECTED);
        if (!add_option(self::AGENCY_SELECTED, $id)) {
            return false;
        }

        return true;
    }
}
