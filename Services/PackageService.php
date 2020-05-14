<?php

namespace Services;

class PackageService
{
    /**
     * Get package of quotation.
     *
     * @param array $quotation
     * @return array $packages
     */
    public function getPackageQuotation($quotation)
    {
        $response = null;

        if (empty($quotation) || is_null($quotation)) {
            return $response;
        }
        foreach($quotation as $item){

            if(!isset($item->id) || is_null($item->id)) {
                continue;
            }

            if (isset($item->packages)) {
                foreach($item->packages as $key => $package) {
                    $response[$item->id] = (object) [
                        'largura' => $package->dimensions->width,
                        'altura' => $package->dimensions->height,
                        'comprimento' => $package->dimensions->length,
                        'peso' => $package->weight
                    ];
                }
            } elseif (isset($item->volumes)) {
                foreach($item->volumes as $key => $volume) {
                    $response[$item->id] = (object) [
                        'largura' => $volume->width,
                        'altura' => $volume->height,
                        'comprimento' => $volume->length,
                        'peso' => $volume->weight
                    ];
                }
            } else {
                continue;
            }
        }
        return $response;
    }
}