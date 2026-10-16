<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Order;

use MelhorEnvio\Http\Controllers\AjaxHandlerContract;
use MelhorEnvio\Http\Controllers\Order\OrderInvoiceKeyMetaBoxController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NFeXmlUploadController extends AjaxHandlerContract {

	private const MAX_SIZE_BYTES = 512000;
	private const NFE_NAMESPACE  = 'http://www.portalfiscal.inf.br/nfe';

	public function __construct() {
		parent::__construct( 'upload_nfe_xml', 'edit_shop_orders' );
	}

	protected function process(): void {
		$orderId = absint( $this->getInput( 'order_id' ) );

		if ( ! $orderId ) {
			$this->sendError( array( 'message' => 'ID do pedido inválido.' ), 422 );
			return;
		}

		$order = wc_get_order( $orderId );

		if ( ! $order instanceof \WC_Order ) {
			$this->sendError( array( 'message' => 'Pedido não encontrado.' ), 422 );
			return;
		}

		if ( empty( $_FILES['nfe_xml'] ) || $_FILES['nfe_xml']['error'] !== UPLOAD_ERR_OK ) {
			$this->sendError( array( 'message' => 'Arquivo não recebido ou erro no upload.' ), 422 );
			return;
		}

		if ( $_FILES['nfe_xml']['size'] > self::MAX_SIZE_BYTES ) {
			$this->sendError( array( 'message' => 'Arquivo excede o limite de 500 KB.' ), 422 );
			return;
		}

		$tmpPath = $_FILES['nfe_xml']['tmp_name'];

		$finfo    = finfo_open( FILEINFO_MIME_TYPE );
		$mimeType = finfo_file( $finfo, $tmpPath );
		finfo_close( $finfo );

		if ( ! in_array( $mimeType, array( 'text/xml', 'application/xml' ), true ) ) {
			$this->sendError( array( 'message' => 'O arquivo deve ser um XML válido.' ), 422 );
			return;
		}

		libxml_use_internal_errors( true );
		$xml = simplexml_load_file( $tmpPath );
		libxml_clear_errors();

		if ( $xml === false ) {
			$this->sendError( array( 'message' => 'Não foi possível processar o XML.' ), 422 );
			return;
		}

		$xml->registerXPathNamespace( 'nfe', self::NFE_NAMESPACE );

		$infNFe = $xml->xpath( '//nfe:infNFe' );

		if ( empty( $infNFe ) ) {
			$this->sendError( array( 'message' => 'XML não é uma NF-e válida (infNFe não encontrado).' ), 422 );
			return;
		}

		$chNFeNodes = $xml->xpath( '//nfe:protNFe/nfe:infProt/nfe:chNFe' );
		$chNFe      = ! empty( $chNFeNodes ) ? (string) $chNFeNodes[0] : '';

		if ( ! $chNFe ) {
			$idAttr = (string) ( $infNFe[0]->attributes()['Id'] ?? '' );
			$chNFe  = preg_replace( '/^NFe/', '', $idAttr );
		}

		if ( ! $chNFe || ! ctype_digit( $chNFe ) || strlen( $chNFe ) !== 44 ) {
			$this->sendError( array( 'message' => 'Chave de acesso inválida ou não encontrada no XML.' ), 422 );
			return;
		}

		$rawXml = file_get_contents( $tmpPath );

		$order->update_meta_data( OrderInvoiceKeyMetaBoxController::META_KEY, $chNFe );
		$order->update_meta_data( '_me_invoice_xml_danfe', $rawXml );
		$order->save();

		$this->sendSuccess( array( 'key' => $chNFe ) );
	}
}
