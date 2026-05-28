<?php

namespace MelhorEnvio\Services;

class NoticeInterviewService {

	const END_DATE = '2026-06-10';
	const LINK_URL = 'https://tally.so/r/dWvNQo';

	public function isActive(): bool {
		$tz  = wp_timezone();
		$now = new \DateTime( 'now', $tz );
		$end = new \DateTime( self::END_DATE . ' 23:59:59', $tz );
		return $now <= $end;
	}

	public function insertNotice(): void {
		if ( ! $this->isActive() ) {
			return;
		}

		add_action(
			'admin_notices',
			function () {
				echo wp_kses(
					'<div style="background:#FFF3CD;border-left:4px solid #F0AD00;padding:12px 16px;margin:5px 0 15px;font-size:14px;">
						&#127873; <strong>Ganhe 5 vouchers de desconto no Melhor Envio!</strong>
						<a href="' . self::LINK_URL . '" target="_blank" rel="noopener"
						   style="color:#0071A1;font-weight:bold;text-decoration:underline;">
							Clique aqui e participe.
						</a>
					</div>',
					array(
						'div'    => array( 'style' => array() ),
						'strong' => array(),
						'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array(), 'style' => array() ),
					)
				);
			}
		);
	}
}
