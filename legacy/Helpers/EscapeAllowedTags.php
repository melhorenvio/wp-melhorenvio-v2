<?php

namespace MelhorEnvio\Helpers;

class EscapeAllowedTags {


	private const TAGS_AND_ATTRIBUTES = array(
		'div'    => array(
			'id'    => array(),
			'class' => array(),
			'style' => array(),
		),
		'input'  => array(
			'type'        => array(),
			'id'          => array(),
			'value'       => array(),
			'maxlength'   => array(),
			'class'       => array(),
			'placeholder' => array(),
			'onkeyup'     => array(),
			'onkeydown'   => array(),
		),
		'p'      => array(),
		'img'    => array(
			'src' => array(),
		),
		'table'  => array(
			'class' => array(),
		),
		'thead'  => array(),
		'tbody'  => array(),
		'small'  => array(
			'id'    => array(),
			'class' => array(),
		),
		'tr'     => array(),
		'td'     => array(),
		'strong' => array(),
		'style'  => array(),
		'form'   => array(),
		'a'      => array(
			'href'   => array(),
			'rel'    => array(),
			'target' => array(),
		),
	);

	/**
	 * @param array $value
	 */
	public static function allow_tags( $tags ) {

		$allowed_tags_attr = array();
		foreach ( $tags as $tag ) {
			if ( isset( self::TAGS_AND_ATTRIBUTES[ $tag ] ) ) {
				$allowed_tags_attr[ $tag ] = self::TAGS_AND_ATTRIBUTES[ $tag ];
			}
		}

		return $allowed_tags_attr;
	}
}
