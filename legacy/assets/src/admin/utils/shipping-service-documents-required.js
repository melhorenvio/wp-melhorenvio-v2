import shippingServices  from "./shipping-services";

const REQUIRED_DOCUMENTS = [
    shippingServices.CORREIOS_PAC,
    shippingServices.CORREIOS_SEDEX,
    shippingServices.CORREIOS_MINI
];

export default REQUIRED_DOCUMENTS