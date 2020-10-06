<?php

namespace Gianism\Controller;

use Gianism\Bootstrap;
use Gianism\Helper\Option;
use Gianism\Pattern\AbstractController;

class Network extends AbstractController {
	
	/**
	 * Network constructor.
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = [] ) {
		// Add network notice
		add_action( 'admin_notices', [ $this, 'network_notice' ] );
		// Set role.
		add_action( 'gianism_before_set_login_cookie', [ $this, 'set_role' ], 1, 2 );
	}
	
	/**
	 * Detect if Gianism can network available.
	 *
	 * @return bool
	 */
	public function network_available() {
		return is_multisite() && ! is_subdomain_install();
	}
	
	/**
	 * Detect if this is child site.
	 */
	public function is_child_site() {
		return is_multisite() && ( $this->option->get_parent_blog_id() !== get_current_blog_id() );
	}
	
	/**
	 * Set user role for child blog.
	 *
	 * @param int    $user_id
	 * @param string $service_name
	 */
	public function set_role( $user_id, $service_name ) {
		$blog_id = (int) $this->session->get( 'blog_id' );
		if ( ! $blog_id ) {
			// Do nothing.
			return;
		}
		$should_assign_default_role = apply_filters( 'gianism_should_assign_default_role', $this->option->is_network_activated() );
		if ( ! $should_assign_default_role ) {
			return;
		}
		// Get blog role for current user.
		$user = new \WP_User( $user_id, '', $blog_id );
		if ( empty( $user->roles ) ) {
			// This user has no role for child site.
			$base_role = apply_filters( '', get_blog_option( $blog_id, 'default_role' ) );
			$user->set_role( $base_role );
		}
	}
	
	/**
	 * Display notices
	 */
	public function network_notice() {
		if ( ! $this->network_available() ) {
			// This is not network available install.
			return;
		}
		if ( Admin::get_instance()->no_nag_notice() ) {
			return;
		}
		try {
			if ( current_user_can( 'manage_network' ) && ! $this->option->is_network_activated() ) {
				throw new \Exception( __( 'Gianism provides network sites supports. Please consider network activation.', 'wp-gianism' ) );
			}
		} catch ( \Exception $e ) {
			printf( '<div class="notice notice-info is-dismissible"><p>%s</p></div>', wp_kses_post( $e->getMessage() ) );
		}
	}
}