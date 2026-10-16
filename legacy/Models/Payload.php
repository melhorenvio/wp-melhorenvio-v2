<?php

namespace MelhorEnvio\Models;

class Payload {

	/**
	 * Identification key for the payload option in WordPress.
	 */
	const POST_META_PAYLOAD = 'wp_melhor_envio_payload';

	/**
	 * function to get payload by post_id
	 *
	 * @param int $postId
	 * @return object
	 */
	public function get( $postId ) {
		$payload = get_post_meta( $postId, self::POST_META_PAYLOAD, true );

		if ( ! empty( $payload ) ) {
			return json_decode( $payload );
		}

		return false;
	}

	/**
	 * Function to save the payload data in WordPress options.
	 *
	 * @param int    $postId
	 * @param object $payload
	 * @return bool
	 */
	public function save( $postId, $payload ) {
		if ( ! empty( $this->get( $postId ) ) ) {
			$this->destroy( $postId );
		}

		return add_post_meta(
			$postId,
			self::POST_META_PAYLOAD,
			json_encode( $payload, JSON_UNESCAPED_UNICODE ),
			true
		);
	}

	/**
	 * Function to destroy the seller data in WordPress options.
	 *
	 * @param int $postId
	 * @return bool
	 */
	public function destroy( $postId ) {
		return delete_post_meta( $postId, self::POST_META_PAYLOAD );
	}
}
