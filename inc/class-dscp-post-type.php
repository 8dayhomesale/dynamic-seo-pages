<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DSCP_Post_Type {

	private static $_instance;

	/**
	 * Setup actions and filters
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_cpt' ) );
		add_filter( 'post_updated_messages', array( $this, 'filter_post_updated_messages' ) );
		add_filter( 'enter_title_here', array( $this, 'filter_title_here' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 1 );
		add_action( 'admin_enqueue_scripts' , array( $this, 'enqueue_scripts_css' ) );
		add_action( 'edit_form_after_title', array( $this, 'output_explain' ) );
		add_filter( 'manage_dscp_page_posts_columns' , array( $this, 'filter_columns' ) );
		add_action( 'manage_dscp_page_posts_custom_column' , array( $this, 'add_custom_columns' ), 10, 2 );
		add_shortcode( 'dscp_variable', array( $this, 'variable_shortcode' ) );
		add_action( 'init', array( $this, 'rewrite_urls' ), 100 );
		add_action( 'template_redirect', array( $this, 'display_template' ) );
		add_filter( 'get_the_excerpt', array( $this, 'apply_shortcodes_excerpt' ), 10, 2 );
		add_filter( 'the_title', array( $this, 'apply_shortcodes_title' ), 10, 2 );
		add_filter( 'wpseo_title', array( $this, 'apply_shortcodes_yoast') );
		add_filter( 'wpseo_metadesc', array( $this, 'apply_shortcodes_yoast') );
	}

	public function apply_shortcodes_yoast( $text ) {
		return do_shortcode( $text );
	}

	public function apply_shortcodes_excerpt( $excerpt, $post ) {
		if ( 'dscp_page' !== get_post_type( $post ) ) {
			return $excerpt;
		}

		return do_shortcode( $excerpt );
	}

	public function apply_shortcodes_title( $title, $post_id ) {
		if ( 'dscp_page' !== get_post_type( $post_id ) ) {
			return $title;
		}

		return do_shortcode( $title );
	}

	public function display_template() {
		global $wp_query;

		$sub_page_id = get_query_var( 'sub_page_id' );

		if ( empty( $sub_page_id ) ) {
			return;
		}

		global $wp_query, $wp_the_query, $post;

		$sub_page = get_post( $sub_page_id );

		$wp_query->query['pagename'] = '';
		$wp_query->query['name'] = $sub_page->post_name;

		$wp_query->is_page = false;
		$wp_query->query_vars['p'] = 0;
		$wp_query->query_vars['name'] = $sub_page->post_name;
		$wp_query->query_vars['posts'] = array( $sub_page );
		$wp_query->posts = array( $sub_page );
		$wp_query->post = array( $sub_page );
		$wp_query->query_vars['pagename'] = '';
		$wp_query->queried_object = $sub_page;
		$wp_query->is_single = true;

		$post = $sub_page;

		$wp_the_query = $wp_query;
	}

	public function rewrite_urls() {
		$sub_pages = get_transient( 'dscp_sub_pages' );

		add_rewrite_tag( '%dscp_variable_1%', '([^/]+)' );
		add_rewrite_tag( '%dscp_variable_2%', '([^/]+)' );
		add_rewrite_tag( '%dscp_variable_3%', '([^/]+)' );
		add_rewrite_tag( '%dscp_variable_4%', '([^/]+)' );
		add_rewrite_tag( '%dscp_variable_5%', '([^/]+)' );
		add_rewrite_tag( '%sub_page_id%', '([0-9]+)' );

		if ( false === $sub_pages ) {
			$sub_pages_query = new WP_Query( array(
				'post_type' => 'dscp_page',
				'post_status' => 'publish',
				'posts_per_page' => 200,
				'no_found_rows' => true,
				'fields' => 'ids',
			) );

			$sub_pages = $sub_pages_query->posts;

			// set_transient( 'dscp_sub_pages', $sub_pages, DAY_IN_SECONDS );
		}

		foreach ( $sub_pages as $sub_page_id ) {
			$base_page_id = get_post_meta( $sub_page_id, 'dscp_base_page', true );
			$variable_url = get_post_meta( $sub_page_id, 'dscp_variable_url', true );

			if ( empty( $base_page_id ) || empty( $variable_url ) ) {
				continue;
			}

			$base_page = get_post( $base_page_id );

			if ( empty( $base_page ) || 'publish' !== get_post_status( $base_page_id ) ) {
				continue;
			}

			$variable_url = trim( $variable_url, '/' );

			if ( empty( $variable_url ) ) {
				continue;
			}

			$variable_url = preg_replace( '#%variable%#is', '([^/]+)', $variable_url );

			add_rewrite_rule( '^' . $base_page->post_name . '/' . $variable_url . '/?$', 'index.php?pagename=' . $base_page->post_name . '&dscp_variable_1=$matches[1]&dscp_variable_2=$matches[2]&dscp_variable_3=$matches[3]&dscp_variable_4=$matches[4]&dscp_variable_5=$matches[5]&sub_page_id=' . $sub_page_id, 'top' );
		}
	}

	public function variable_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'number' => 1,
			'format' => 'uppercase-words',
			'default' => '',
		), $atts, 'dscp_variable' );

		if ( ! empty( $atts['number'] ) ) {
			$atts['number'] = (int) $atts['number'];
		}

		$variable = get_query_var( 'dscp_variable_' . $atts['number'] );

		if ( empty( $variable ) ) {
			return $atts['default'];
		}


		if ( ! empty( $atts['format'] ) ) {
			if ( 'uppercase-words' === $atts['format'] ) {
				return esc_html( ucwords( $variable ) );
			} elseif ( 'raw' === $atts['format'] ) {
				return esc_html( $variable );
			}
		}

		return $atts['default'];
	}

	public function filter_columns( $columns ) {
		$columns['dscp_base_page'] = esc_html__( 'Base Page', 'dynamic-seo-child-pages' );

		unset( $columns['date'] );

		$columns['date'] = esc_html__( 'Date', 'dynamic-seo-child-pages' );

		return $columns;
	}

	public function add_custom_columns( $column, $post_id ) {
		if ( 'dscp_base_page' == $column ) {
			$parent_page = get_post_meta( $post_id, 'dscp_base_page', true );

			if ( ! empty( $parent_page ) ) {
				echo '<a href="' . esc_html( get_edit_post_link( $parent_page ) ) . '">' . esc_html( get_the_title( $parent_page ) ) . '</a>';
			} else {
				esc_html_e( 'None', 'dynamic-seo-child-pages' );
			}
		}
	}

	public function output_explain( $post ) {
		if ( 'dscp_page' !== get_post_type( $post ) ) {
			return;
		}
		?>
		<p class="dscp-explain description">
			<?php _e( 'You can insert a URL variable into your content using the shortcode <strong>[dscp_variable]</strong>. Variables are set in the &quot;Variable URL Field&quot; defined in the &quot;Settings&quot; metabox below. If you have defined multiple variables, you can use a shortcode in the form of <strong>[dscp_variable number="2"]</strong>.', 'dynamic-seo-child-pages' ); ?>
		</p>
		<?php
	}

	public function enqueue_scripts_css() {
		global $pagenow;

		if ( 'dscp_page' == get_post_type() || ( isset( $_GET['post_type'] ) && 'dscp_page' === $_GET['post_type'] ) ) {
			wp_enqueue_style( 'dscp-admin', plugins_url( 'assets/admin.css', dirname( __FILE__ ) ) );
		}
	}

	public function save_post( $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || 'revision' == get_post_type( $post_id ) ) {
			return;
		}

		if ( empty( $_POST['dscp_settings_nonce'] ) || ! wp_verify_nonce( $_POST['dscp_settings_nonce'], 'dscp_settings_action' ) ) {
			return;
		}

		if ( empty( $_POST['dscp_variable_url'] ) ) {
			delete_post_meta( $post_id, 'dscp_variable_url' );
		} else {
			update_post_meta( $post_id, 'dscp_variable_url', sanitize_text_field( $_POST['dscp_variable_url'] ) );
		}

		if ( empty( $_POST['dscp_base_page'] ) ) {
			delete_post_meta( $post_id, 'dscp_base_page' );
		} else {
			update_post_meta( $post_id, 'dscp_base_page', absint( $_POST['dscp_base_page'] ) );
		}

		$this->rewrite_urls();

		flush_rewrite_rules( false );
	}

	public function filter_title_here( $text, $post ) {
		if ( 'dscp_page' !== get_post_type( $post ) ) {
			return $text;
		}
		return esc_html__( 'Enter dynamic subpage title', 'dynamic-seo-child-pages' );
	}

	public function filter_post_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['dscp_pages'] = array(
			0 => '',
			1 => sprintf( __( 'Dynamic SEO Child Page updated. <a href="%s">View Dynamic SEO Page</a>', 'dynamic-seo-child-pages' ), esc_url( get_permalink( $post_ID ) ) ),
			2 => __( 'Custom field updated.', 'dynamic-seo-child-pages' ),
			3 => __( 'Custom field deleted.', 'dynamic-seo-child-pages' ),
			4 => __( 'Dynamic SEO Child Page updated.', 'dynamic-seo-child-pages' ),
			5 => isset( $_GET['revision'] ) ? sprintf( __( ' Dynamic SEO Child Page restored to revision from %s', 'dynamic-seo-child-pages' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( 'Dynamic SEO Child Page published. <a href="%s">View Dynamic SEO Child Page</a>', 'dynamic-seo-child-pages' ), esc_url( get_permalink( $post_ID ) ) ),
			7 => __( 'Dynamic SEO Child Page saved.', 'dynamic-seo-child-pages' ),
			8 => sprintf( __( 'Dynamic SEO Child Page submitted. <a target="_blank" href="%s">Preview Dynamic SEO Child Page</a>', 'dynamic-seo-child-pages' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9 => sprintf( __( 'Dynamic SEO Child Page scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Dynamic SEO Child Page</a>', 'dynamic-seo-child-pages' ),
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( 'Dynamic SEO Child Page draft updated. <a target="_blank" href="%s">Preview Dynamic SEO Child Page</a>', 'dynamic-seo-child-pages' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}

	public function setup_cpt() {

		$labels = array(
			'name' => esc_html__( 'Dynamic SEO Child Pages', 'dynamic-seo-child-pages' ),
			'singular_name' => esc_html__( 'Dynamic SEO Child Page', 'dynamic-seo-child-pages' ),
			'add_new' => esc_html__( 'Add New', 'dynamic-seo-child-pages' ),
			'add_new_item' => esc_html__( 'Add New Dynamic SEO Child Page', 'dynamic-seo-child-pages' ),
			'edit_item' => esc_html__( 'Edit Dynamic SEO Child Page', 'dynamic-seo-child-pages' ),
			'new_item' => esc_html__( 'New Dynamic SEO Child Page', 'dynamic-seo-child-pages' ),
			'all_items' => esc_html__( 'All Dynamic SEO Child Pages', 'dynamic-seo-child-pages' ),
			'view_item' => esc_html__( 'View Dynamic SEO Child Page', 'dynamic-seo-child-pages' ),
			'search_items' => esc_html__( 'Search Dynamic SEO Child Pages', 'dynamic-seo-child-pages' ),
			'not_found' => esc_html__( 'No Dynamic SEO Child Pages found', 'dynamic-seo-child-pages' ),
			'not_found_in_trash' => esc_html__( 'No Dynamic SEO Child Pages found in trash', 'dynamic-seo-child-pages' ),
			'parent_item_colon' => '',
			'menu_name' => esc_html__( 'Dynamic SEO Child Pages', 'dynamic-seo-child-pages' ),
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
			'rewrite' => array( 'slug' => 'dscp-placeholder' ),
			'menu_icon' => 'dashicons-format-aside',
			'supports' => array( 'title', 'editor', 'excerpt', 'author' ),
		);

		register_post_type( 'dscp_page', $args );
	}

	public function add_meta_boxes() {
		add_meta_box( 'dscp_settings', esc_html__( 'Settings', 'dynamic-seo-child-pages' ), array( $this, 'output_settings_meta' ), 'dscp_page', 'normal', 'high' );
	}

	public function output_settings_meta( $post ) {
		$variable_url = get_post_meta( $post->ID, 'dscp_variable_url', true );

		if ( empty( $variable_url ) ) {
			$variable_url = '/%variable%/';
		}

		$base_page = get_post_meta( $post->ID, 'dscp_base_page', true );
		if ( empty( $base_page ) ) {
			$base_page = 0;
		}

		wp_nonce_field( 'dscp_settings_action', 'dscp_settings_nonce' );
		?>
			<div class="dscp-field">
				<label for="dscp_variable_url"><?php esc_html_e( 'Base Page:', 'dynamic-seo-child-pages' ); ?></label>
				<?php wp_dropdown_pages( array( 'selected' => $base_page, 'name' => 'dscp_base_page', 'class' => 'dscp-page-chooser', 'show_option_no_change' => esc_html__( 'Choose a Page', 'dynamic-seo-child-pages' ) ) ); ?>
				<p class="description"><?php esc_html_e( 'Dynamic subpages will be applied to this page.', 'dynamic-seo-child-pages' ); ?></p>
			</div>

			<div class="dscp-field">
				<label for="dscp_variable_url"><?php esc_html_e( 'Variable URL:', 'dynamic-seo-child-pages' ); ?></label>
				<input class="widefat" type="text" id="dscp_variable_url" name="dscp_variable_url" value="<?php echo esc_attr( $variable_url ); ?>" />
				<p class="description"><?php esc_html_e( 'The variable URL will be appended to your base page e.g.', 'dynamic-seo-child-pages' ); ?> <strong><?php echo esc_url( home_url( 'locations/%variable%/' ) ); ?></strong></p>
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

DSCP_Post_Type::factory();
