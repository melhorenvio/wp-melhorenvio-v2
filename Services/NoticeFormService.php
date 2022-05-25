<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Helpers\EscapeAllowedTags;

class NoticeFormService {



	const SHOW = 'show_form_melhor_envio';

	const HIDE = 'hide_form_melhor_envio';

	const OPTION_SHOW_FORM = 'hide_form_melhor_envio';

	/**
	 * Function to check whether to display and insert the search form alert on the administrative page
	 */
	public function insertForm() {
		$showForm = $this->getVisibilityForm();
		$show     = self::SHOW;
		if ( $showForm == $show ) {
			add_action(
				'admin_notices',
				function () {
					echo wp_kses(
						'<div class="notice info is-dismissible"> 
                    <p><strong>Como podemos melhorar?</strong></p>
                    <p>Gostaríamos de saber mais sobre a sua experiência com o plugin do Melhor Envio 
                    para que possamos aprimorá-lo. 
                    <a href="/wp-admin/admin-ajax.php?action=open_form_melhor_envio">Clique aqui</a> 
                    e nos ajude respondendo a pesquisa.
                    </p>
                </div>',
						EscapeAllowedTags::allow_tags( array( 'div', 'p', 'a' ) )
					);
				}
			);
		}
	}

	/**
	 * Function for obtaining the visibility of the alert for the search form
	 *
	 * @return string
	 */
	public function getVisibilityForm() {
		return get_option( self::OPTION_SHOW_FORM, self::SHOW );
	}

	/**
	 * Function to hide the form alert
	 *
	 * @return bool
	 */
	public function hideForm() {
		delete_option( self::OPTION_SHOW_FORM );
		return add_option( self::OPTION_SHOW_FORM, self::HIDE, true );
	}

	/**
	 * Function to show the form alert
	 *
	 * @return bool
	 */
	public function showForm() {
		delete_option( self::OPTION_SHOW_FORM );
		return add_option( self::OPTION_SHOW_FORM, self::SHOW, true );
	}
}
