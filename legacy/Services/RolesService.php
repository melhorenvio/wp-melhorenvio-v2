<?php

namespace MelhorEnvio\Services;

class RolesService {

	/**
	 *  function to create the "Best shipping" support permission with restricted access.
	 *
	 * @return void
	 */
	public function init() {
		add_action(
			'init',
			function () {
				add_role(
					'melhor-envio-equipe-suporte',
					'Suporte Melhor Envio (limitado)',
					array(
						'read'                    => true,
						'manage_woocommerce'      => true,
						'edit_posts'              => true,
						'upload_plugins'          => true,
						'edit_plugins'            => true,
						'activate_plugins'        => true,
						'update_plugins'          => true,
						'read_product'            => true,
						'manage_product'          => true,
						'read_product'            => true,
						'manage_product'          => true,
						'edit_products'           => true,
						'edit_product'            => true,
						'edit_published_products' => true,
						'edit_others_products'    => true,
					)
				);

                $me_role = get_role('melhor-envio-equipe-suporte');
                $me_role->add_cap('melhor_envio_debug', true);
                $admin_role = get_role('administrator');
                $admin_role->add_cap('melhor_envio_debug', true);
			}
		);
	}
}
