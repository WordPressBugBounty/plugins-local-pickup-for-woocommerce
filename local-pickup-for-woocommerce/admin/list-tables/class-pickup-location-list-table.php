<?php
/**
 * Handles plugin rules listing
 * 
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * DSLPFW_Pickup_Location_List_Table class.
 *
 * @extends WP_List_Table
 */
if ( ! class_exists( 'DSLPFW_Pickup_Location_List_Table' ) ) {

	class DSLPFW_Pickup_Location_List_Table extends WP_List_Table {

        private static $dslpfw_found_items = 0;
        /**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			parent::__construct( array(
				'singular' => 'pickup_location',
				'plural'   => 'pickup_locations',
				'ajax'     => false
			) );
		}

        /**
		 * get_columns function.
		 *
		 * @return  array
		 * @since 1.0.0
		 *
		 */
		public function get_columns() {
			$column_array = array(
				'cb'                => '<input type="checkbox" />',
				'title'             => esc_html__( 'Title', 'local-pickup-for-woocommerce' ),
            );
            if( class_exists('WPML_Custom_Columns') ){
				global $sitepress;
				$lang_column = new WPML_Custom_Columns($sitepress);
				$column_array['icl_translations'] = $lang_column->get_flags_column();
			}
            $column_array += array(
				'address'           => esc_html__( 'Address', 'local-pickup-for-woocommerce' ),
                'city'              => esc_html__( 'City', 'local-pickup-for-woocommerce' ),
                'state'             => esc_html__( 'State', 'local-pickup-for-woocommerce' ),
                'country'           => esc_html__( 'Country', 'local-pickup-for-woocommerce' ),
                'postcode'          => esc_html__( 'Postcode', 'local-pickup-for-woocommerce' ),
				'status'            => esc_html__( 'Status', 'local-pickup-for-woocommerce' ),
				'date'              => esc_html__( 'Date', 'local-pickup-for-woocommerce' ),
			);
			return $column_array;
		}

        /**
		 * get_sortable_columns function.
		 *
		 * @return array
		 * @since 1.0.0
		 *
		 */
		protected function get_sortable_columns() {
			$columns = array(
				'title'  => array( 'title', true ),
				'date'   => array( 'date', false ),
			);

			return $columns;
		}

        /**
		 * Checkbox column
		 *
		 * @param string
		 *
		 * @return mixed
		 * @since 1.0.0
		 *
		 */
		public function column_cb( $item ) {
			if ( ! $item->ID ) {
				return;
			}

			return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', 'method_id_cb', esc_attr( $item->ID ) );
		}

        /**
		 * Output the shipping name column.
		 *
		 * @param object $item
		 *
		 * @return string
		 * @since 1.0.0
		 *
		 */
		public function column_title( $item ) {
			$editurl = add_query_arg( array(
				'page'   => 'dslpfw-pickup-location-list',
				'id'   => $item->ID,
				'action' => 'edit',
			), admin_url( 'admin.php' ) );

			$method_name = '<strong>
                            <a href="' . esc_url( $editurl ) . '" class="row-title">' . esc_html( $item->post_title ) . '</a>
                        </strong>';

			return wp_kses( $method_name, dslpfw()->dslpfw_allowed_html_tags() );
		}

        /**
		 * Output the WPML translation column.
		 *
		 * @param object $item
		 *
		 * @return mixed WPML translation colum HTML;
		 * @since 1.0.0
		 *
		 */
        public function column_icl_translations( $item ){
			global $sitepress;
			$language_column = new WPML_Custom_Columns($sitepress); // @phpstan-ignore-line
			return $language_column->add_content_for_posts_management_column( 'icl_translations', $item->ID );
		}

        /**
		 * Output the shipping name column.
		 *
		 * @param object $item
		 *
		 * @return string
		 * @since 1.0.0
		 *
		 */
		public function column_address( $item ) {

            if ( !isset($item->ID) || 0 === $item->ID ) {
				return '-';
			}

			$pickup_location_id = $item->ID;
            $pickup_location = new \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location( $pickup_location_id );

            return wp_kses( $pickup_location->get_address()->get_street_address('string', ', '), dslpfw()->dslpfw_allowed_html_tags() );
		}

        /**
		 * Output the shipping name column.
		 *
		 * @param object $item
		 *
		 * @return string
		 * @since 1.0.0
		 *
		 */
		public function column_city( $item ) {

            if ( !isset($item->ID) || 0 === $item->ID ) {
				return '-';
			}

			$pickup_location_id = $item->ID;
            $pickup_location = new \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location( $pickup_location_id );

            return esc_html($pickup_location->get_address()->get_city());
		}

        /**
		 * Output the shipping name column.
		 *
		 * @param object $item
		 *
		 * @return string
		 * @since 1.0.0
		 *
		 */
		public function column_state( $item ) {

            if ( !isset($item->ID) || 0 === $item->ID ) {
				return '-';
			}

			$pickup_location_id = $item->ID;
            $pickup_location = new \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location( $pickup_location_id );

            return esc_html($pickup_location->get_address()->get_state_name());
		}

        /**
		 * Output the shipping name column.
		 *
		 * @param object $item
		 *
		 * @return string
		 * @since 1.0.0
		 *
		 */
		public function column_country( $item ) {

            if ( !isset($item->ID) || 0 === $item->ID ) {
				return '-';
			}

			$pickup_location_id = $item->ID;
            $pickup_location = new \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location( $pickup_location_id );

            return esc_html($pickup_location->get_address()->get_country_name());
		}

        /**
		 * Output the shipping name column.
		 *
		 * @param object $item
		 *
		 * @return string
		 * @since 1.0.0
		 *
		 */
		public function column_postcode( $item ) {

            if ( !isset($item->ID) || 0 === $item->ID ) {
				return '-';
			}

			$pickup_location_id = $item->ID;
            $pickup_location = new \DSLPFW_Local_Pickup_WooCommerce_Pickup_Location( $pickup_location_id );

            return esc_html($pickup_location->get_address()->get_postcode());
		}

        /**
		 * Output the method enabled column.
		 *
		 * @param object $item
		 *
		 * @return string
		 */
		public function column_status( $item ) {
			if ( 0 === $item->ID ) {
				return esc_html__( 'Trash', 'local-pickup-for-woocommerce' );
			}

			$item_status 		= get_post_meta( $item->ID, 'dslpfw_settings_status', true );
			$fees_status     	= get_post_status( $item->ID );
			$fees_status_chk 	= ( ( ! empty( $fees_status ) && 'publish' === $fees_status ) || empty( $fees_status ) ) ? 'checked' : '';
			if ( 'on' === $item_status ) {
				$status = '<label class="switch">
								<input type="checkbox" class="status_switch" name="dslpfw_settings_status" value="on" '.esc_attr( $fees_status_chk ).' data-dslpfwid="'. esc_attr( $item->ID ) .'">
								<div class="slider round"></div>
							</label>';
			} else {
				$status = '<label class="switch">
								<input type="checkbox" class="status_switch" name="dslpfw_settings_status" value="on" '.esc_attr( $fees_status_chk ).' data-dslpfwid="'. esc_attr( $item->ID ) .'">
								<div class="slider round"></div>
							</label>';
			}

			return $status;
		}

        /**
		 * Output the method date column.
		 *
		 * @param object $item
		 *
		 * @return mixed $item->post_date;
		 * @since 1.0.0
		 *
		 */
		public function column_date( $item ) {
			if ( 0 === $item->ID ) {
				return esc_html__( 'N/A', 'local-pickup-for-woocommerce' );
			}
            
            $date_obj = date_create($item->post_date);
            $new_format = sprintf( '%s at %s', date_format( $date_obj, get_option('date_format')), date_format( $date_obj, get_option('time_format')));

			return $new_format;
		}
        
        /**
         * get_sortable_columns function.
		 *
		 * @since 1.0.0
		 */
		public function no_items() {
            esc_html_e( 'Pickup location not found.', 'local-pickup-for-woocommerce' );
		}

        /**
		 * Generates and displays row action links.
		 *
		 * @param object $item Link being acted upon.
		 * @param string $column_name Current column name.
		 * @param string $primary Primary column name.
		 *
		 * @return string Row action output for links.
		 * @since 1.0.0
		 *
		 */
		protected function handle_row_actions( $item, $column_name, $primary ) {
			if ( $primary !== $column_name ) {
				return '';
			}

			$edit_method_url = add_query_arg( array(
				'page'   => 'dslpfw-pickup-location-list',
				'id'   => $item->ID,
				'action' => 'edit',
			), admin_url( 'admin.php' ) );
			$editurl         = esc_url($edit_method_url);

			$delete_method_url = add_query_arg( array(
				'page'   => 'dslpfw-pickup-location-list',
				'action' => 'delete',
				'id'   => $item->ID
			), admin_url( 'admin.php' ) );
			$delurl            = wp_nonce_url( $delete_method_url, 'del_' . $item->ID, '_wpnonce' );

            $duplicate_method_url = add_query_arg( array(
				'page'   => 'dslpfw-pickup-location-list',
				'action' => 'duplicate',
				'id'   => $item->ID
			), admin_url( 'admin.php' ) );
			$duplicateurl      = wp_nonce_url( $duplicate_method_url, 'duplicate_' . $item->ID, '_wpnonce' );

			$actions            = array();
            if( DSLPFW__DEV_MODE ) {
			    $actions['ID']      = esc_html__( '#', 'local-pickup-for-woocommerce' ) . $item->ID;
            }
			$actions['edit']    = '<a href="' . esc_url($editurl) . '">' . esc_html__( 'Edit', 'local-pickup-for-woocommerce' ) . '</a>';
			$actions['delete']  = '<a href="' . esc_url($delurl) . '">' . esc_html__( 'Delete', 'local-pickup-for-woocommerce' ) . '</a>';
			$actions['duplicate']   = '<a href="' . esc_url($duplicateurl) . '">' . esc_html__( 'Duplicate', 'local-pickup-for-woocommerce' ) . '</a>';

			return $this->row_actions( $actions );
		}

        /**
		 * Get Methods to display
		 *
		 * @since 1.0.0
		 */
		public function prepare_items() {
            $this->prepare_column_headers();
			$per_page = $this->get_items_per_page( 'dslpfw_rule_per_page' );

			$get_search  = filter_input( INPUT_POST, 's', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$get_orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$get_order   = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$get_status  = filter_input( INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

			$args = array(
				'posts_per_page' => $per_page,
				'orderby'        => array(
                    'menu_order'    => 'ASC',
                    'post_date'     => 'DESC',
                ),
				'offset'         => ( $this->get_pagenum() - 1 ) * $per_page,
                'post_type'      => DSLPFW_POST_TYPE,
			);

            //Search with pagination
			if ( isset( $get_search ) && ! empty( $get_search ) ) {
				$new_url = esc_url_raw( add_query_arg('s', $get_search) );
				wp_safe_redirect($new_url);
				exit;
			} elseif( isset( $get_search ) && empty( $get_search ) ) {
				$new_url = esc_url_raw( remove_query_arg('s') );
				wp_safe_redirect($new_url);
				exit;
			} else {
				$get_search = filter_input( INPUT_GET, 's', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
				if ( isset( $get_search ) && ! empty( $get_search ) ) {
					$args['s'] = trim( wp_unslash( $get_search ) );
				}
			}

			if ( isset( $get_orderby ) && ! empty( $get_orderby ) ) {
				if ( 'title' === $get_orderby ) {
					$args['orderby'] = 'title';
                } elseif ( 'date' === $get_orderby ) {
					$args['orderby'] = 'date';
				}
			}

			if ( isset( $get_order ) && ! empty( $get_order ) ) {
				if ( 'asc' === strtolower( $get_order ) ) {
					$args['order'] = 'ASC';
				} elseif ( 'desc' === strtolower( $get_order ) ) {
					$args['order'] = 'DESC';
				}
			}

			if( !empty($get_status) ){
                if( 'enable' === strtolower($get_status) ){
                    $args['post_status'] = 'publish';
                } elseif( 'disable' === strtolower($get_status) ) {
                    $args['post_status'] = 'draft';
                } else {
                    $args['post_status'] = 'all';
                }
            }

			$this->items = $this->dslpfw_find( $args );

			$total_items = $this->dslpfw_count_method();

			$total_pages = ceil( $total_items / $per_page );

			$this->set_pagination_args( array(
				'total_items' => $total_items,
				'total_pages' => $total_pages,
				'per_page'    => $per_page,
			) );
		}

        /**
		 * Find post data
		 *
		 * @param mixed $args
		 * @param string $get_orderby
		 *
		 * @return array $posts
		 * @since 1.0.0
		 *
		 */
		public static function dslpfw_find( $args = '' ) {
			$defaults = array(
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'offset'         => 0,
				'orderby'        => array (
                    'ID' => 'ASC',
                )
			);

			$args = wp_parse_args( $args, $defaults );

			$args['post_type'] = DSLPFW_POST_TYPE;

			$dslpfw_query   = new WP_Query( $args );
			$posts          = $dslpfw_query->query( $args );

            self::$dslpfw_found_items = $dslpfw_query->found_posts;

			return $posts;
		}

        /**
		 * Count post data
		 *
		 * @return string
		 * @since 1.0.0
		 *
		 */
		public static function dslpfw_count_method() {
			return self::$dslpfw_found_items;
		}

        /**
		 * Display bulk action in filter
		 *
		 * @return array $actions
		 * @since 1.0.0
		 *
		 */
		public function get_bulk_actions() {
			$actions = array(
				'disable' => esc_html__( 'Disable', 'local-pickup-for-woocommerce' ),
				'enable'  => esc_html__( 'Enable', 'local-pickup-for-woocommerce' ),
				'delete'  => esc_html__( 'Delete', 'local-pickup-for-woocommerce' )
			);

			return $actions;
		}

        /**
		 * Process bulk actions
		 *
		 * @since 1.0.0
		 */
		public function process_bulk_action() {
            $nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            if( !empty( $nonce ) ){
                $action = 'bulk-' . $this->_args['plural'];

                if ( ! wp_verify_nonce( $nonce, $action ) ) {
                    dslpfw()->dslpfw_updated_message('nonce_check');
                }

                $action = $this->current_action();

                $get_method_id_cb   = filter_input( INPUT_POST, 'method_id_cb', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY );
			    $items              = ! empty( $get_method_id_cb ) ? array_map( 'absint', wp_unslash( $get_method_id_cb ) ) : array();

                switch ( $action ) {

                    case 'delete':
                        foreach ( $items as $dslpfw_id ) {
                            wp_delete_post( $dslpfw_id );
                        }
                        dslpfw()->dslpfw_updated_message('deleted');
                        break;
        
                    case 'enable':
                        foreach ( $items as $dslpfw_id ) {
                            $post_args   = array(
                                'ID'          => $dslpfw_id,
                                'post_status' => 'publish',
                                'post_type'   => DSLPFW_POST_TYPE,
                            );
                            $post_update = wp_update_post( $post_args );
                            if ( $post_update > 0 ) {
                                update_post_meta( $dslpfw_id, 'dslpfw_settings_status', 'on' );
                            }
                        }
                        dslpfw()->dslpfw_updated_message('enabled');
                        break;
        
                    case 'disable':
                        foreach ( $items as $dslpfw_id ) {
                            $post_args   = array(
                                'ID'          => $dslpfw_id,
                                'post_status' => 'draft',
                                'post_type'   => DSLPFW_POST_TYPE,
                            );
                            $post_update = wp_update_post( $post_args );
                            if ( $post_update > 0 ) {
                                update_post_meta( $dslpfw_id, 'dslpfw_settings_status', 'off' );
                            }
                        }
                        dslpfw()->dslpfw_updated_message('disabled');
                        break;

                    default:
                        // do nothing or something else
                        return;
                        break;
                }
            }
        }

        /**
	     * Display the search box.
	     *
	     * @since 1.0.0
	     * @access public
	     *
	     * @param string $text    The 'submit' button label.
	     * @param string $input_id The input id.
	     */
	    public function search_box( $text, $input_id ) {
	    	$input_id = $input_id . '-search-input';
	        ?>
	        <p class="search-box">
				<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php esc_html( $text ); ?>:</label>
				<input type="search" id="<?php echo esc_attr( $input_id ); ?>" placeholder="<?php esc_attr_e( 'Pickup location title', 'local-pickup-for-woocommerce' ) ?>" name="s" value="<?php _admin_search_query(); ?>" />
					<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
			</p>
            <?php
        }
	}
}
