<?php

namespace Models;

class Agency
{
    const AGENCY_SELECTED = 'melhorenvio_agency_jadlog_v2';

    /**
     * function to get the id of agency Jadlog selected.
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
