<?php

class DSP_Post_Type {

	private static $_instance;

	/**
	 * Setup actions and filters
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_cpt' ) );
		add_filter( 'post_updated_messages', array( $this, 'filter_post_updated_messages' ) );
		add_filter( 'get_sample_permalink', array( $this, 'filter_sample_permalink' ), 10, 5 );
		add_filter( 'enter_title_here', array( $this, 'filter_title_here' ), 10, 2 );
		add_filter( 'get_sample_permalink_html', array( $this, 'filter_sample_permalink_html' ), 999, 5 );
		add_action( 'save_post', array( $this, 'save_post' ) );
	}

	public function save_post( $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || 'revision' == get_post_type( $post_id ) ) {
			return;
		}

		if ( empty( $_POST['dsp_settings_nonce'] ) || ! wp_verify_nonce( $_POST['dsp_settings_nonce'], 'dsp_settings_action' ) ) {
			return;
		}

		if ( empty( $_POST['dsp_variable_url'] ) ) {
			delete_post_meta( $post_id, 'dsp_variable_url' );
		} else {
			update_post_meta( $post_id, 'dsp_variable_url', sanitize_text_field( $_POST['dsp_variable_url'] ) );
		}
	}

	public function filter_sample_permalink_html( $html, $post_id, $new_title, $new_slug, $post ) {
		if ( 'dsp_page' !== get_post_type( $post_id ) ) {
			return $html;
		}

		$html = preg_replace( '#<strong>.*?<\/strong>#is', '<strong>' . esc_html__( 'Dynamic Page Base Slug:', 'dynamic-seo-pages' ) . '</strong>', $html );

		return $html;
	}

	public function filter_title_here( $text, $post ) {
		if ( 'dsp_page' !== get_post_type( $post ) ) {
			return $text;
		}
		return esc_html__( 'Enter dynamic page title', 'dynamic-seo-pages' );
	}

	public function filter_sample_permalink( $permalink, $post_id, $title, $name, $post ) {
		return [ home_url( '%postname%/' ), $post->post_name ];
	}

	public function filter_post_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['dsp_pages'] = array(
			0 => '',
			1 => sprintf( __( 'Dynamic SEO Page updated. <a href="%s">View Dynamic SEO Page</a>', 'dynamic-seo-pages' ), esc_url( get_permalink( $post_ID ) ) ),
			2 => __( 'Custom field updated.', 'dynamic-seo-pages' ),
			3 => __( 'Custom field deleted.', 'dynamic-seo-pages' ),
			4 => __( 'Dynamic SEO Page updated.', 'dynamic-seo-pages' ),
			5 => isset( $_GET['revision'] ) ? sprintf( __( ' Dynamic SEO Page restored to revision from %s', 'dynamic-seo-pages' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( 'Dynamic SEO Page published. <a href="%s">View Dynamic SEO Page</a>', 'dynamic-seo-pages' ), esc_url( get_permalink( $post_ID ) ) ),
			7 => __( 'Dynamic SEO Page saved.', 'dynamic-seo-pages' ),
			8 => sprintf( __( 'Dynamic SEO Page submitted. <a target="_blank" href="%s">Preview Dynamic SEO Page</a>', 'dynamic-seo-pages' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9 => sprintf( __( 'Dynamic SEO Page scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Dynamic SEO Page</a>', 'dynamic-seo-pages' ),
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( 'Dynamic SEO Page draft updated. <a target="_blank" href="%s">Preview Dynamic SEO Page</a>', 'dynamic-seo-pages' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}

	public function setup_cpt() {

		$labels = array(
			'name' => esc_html__( 'Dynamic SEO Pages', 'dynamic-seo-pages' ),
			'singular_name' => esc_html__( 'Dynamic SEO Page', 'dynamic-seo-pages' ),
			'add_new' => esc_html__( 'Add New', 'dynamic-seo-pages' ),
			'add_new_item' => esc_html__( 'Add New Dynamic SEO Page', 'dynamic-seo-pages' ),
			'edit_item' => esc_html__( 'Edit Dynamic SEO Page', 'dynamic-seo-pages' ),
			'new_item' => esc_html__( 'New Dynamic SEO Page', 'dynamic-seo-pages' ),
			'all_items' => esc_html__( 'All Dynamic SEO Pages', 'dynamic-seo-pages' ),
			'view_item' => esc_html__( 'View Dynamic SEO Page', 'dynamic-seo-pages' ),
			'search_items' => esc_html__( 'Search Dynamic SEO Pages', 'dynamic-seo-pages' ),
			'not_found' => esc_html__( 'No Dynamic SEO Pages found', 'dynamic-seo-pages' ),
			'not_found_in_trash' => esc_html__( 'No Dynamic SEO Pages found in trash', 'dynamic-seo-pages' ),
			'parent_item_colon' => '',
			'menu_name' => esc_html__( 'Dynamic SEO Pages', 'dynamic-seo-pages' ),
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'dsp-placeholder' ),
			'menu_icon' => 'dashicons-admin-page',
			'register_meta_box_cb' => array( $this, 'add_meta_boxes' ),
			'supports' => array( 'title', 'editor', 'page-attributes', 'excerpt', 'author' ),
		);

		register_post_type( 'dsp_page', $args );
	}

	public function add_meta_boxes() {
		add_meta_box( 'dsp_settings', esc_html__( 'Settings', 'dynamic-seo-pages' ), array( $this, 'output_settings_meta' ), 'dsp_page', 'normal', 'core' );
	}

	public function output_settings_meta( $post ) {
		$variable_url = get_post_meta( $post->ID, 'dsp_variable_url', true );

		if ( empty( $variable_url ) ) {
			$variable_url = '/%variable_1%/';
		}

		wp_nonce_field( 'dsp_settings_action', 'dsp_settings_nonce' );
		?>
			<div>
				<label for="dsp_variable_url"><?php esc_html_e( 'Variable URL:', 'dynamic-seo-pages' ); ?></label>
				<input class="widefat" type="text" id="dsp_variable_url" name="dsp_variable_url" value="<?php echo esc_attr( $variable_url ); ?>" />
				<p class="description"><?php esc_html_e( 'The variable URL will be appended to your dynamic page base slug e.g.', 'dynamic-seo-pages' ); ?> <strong><?php echo esc_url( home_url( 'locations/%variable_1%/' ) ); ?></strong></p>
			</div>
		<?php
	}

	public static function factory() {
		static $instnace;

		if ( empty( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}
}

DSP_Post_Type::factory();
