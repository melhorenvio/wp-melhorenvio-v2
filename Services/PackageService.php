<?php

namespace Services;

use Helpers\DimensionsHelper;

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
                        'largura' => (new DimensionsHelper())->convertUnitDimensionToCentimeter($package->dimensions->width),
                        'altura' => (new DimensionsHelper())->convertUnitDimensionToCentimeter($package->dimensions->height),
                        'comprimento' => (new DimensionsHelper())->convertUnitDimensionToCentimeter($package->dimensions->length),
                        'peso' => (new DimensionsHelper())->convertWeightUnit($package->weight)
                    ];
                }
            } elseif (isset($item->volumes)) {
                foreach($item->volumes as $key => $volume) {
                    $response[$item->id] = (object) [
                        'largura' => (new DimensionsHelper())->convertUnitDimensionToCentimeter($volume->width),
                        'altura' => (new DimensionsHelper())->convertUnitDimensionToCentimeter($volume->height),
                        'comprimento' => (new DimensionsHelper())->convertUnitDimensionToCentimeter($volume->length),
                        'peso' => (new DimensionsHelper())->convertWeightUnit($volume->weight)
                    ];
                }
            } else {
                continue;
            }
        }
        return $response;
    }
}