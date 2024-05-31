<?php

/*
 * Plugin name: Минимальное и максимальное кол-во товара
 * Description:
 * Version: 1.0.0
 * Author: Zurab Shyvarbidze
 * License: GPL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


add_action( 'plugin_loaded', function () {

} );

if ( ! class_exists( 'ZshMinMaxQty' ) ) {
	class ZshMinMaxQty {
		public function __construct() {
			add_filter( 'woocommerce_quantity_input_args', [ $this, 'quantity_args' ], 10, 2 );

			//настройки вариаций товара
			add_action( 'woocommerce_variation_options_dimensions', [ $this, 'variation_settings' ], 10, 3 );
			add_action( 'woocommerce_save_product_variation', [ $this, 'variation_save' ], 10, 2 );

			//  опции WooCommerce
			add_filter( 'woocommerce_get_sections_products', [ $this, 'add_section' ] );
			add_filter( 'woocommerce_get_settings_products', [ $this, 'add_setting' ], 10, 2 );

			// настройки товара
			add_action( 'woocommerce_product_options_inventory_product_data', [ $this, 'product_settings' ] );
			add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_settings' ] );
		}


		public function quantity_args( $args, $product ) {

			$args['min_value'] = ( $min_value = get_post_meta( $product->get_id(), 'min_qty', true ) ) ? $min_value : 1;
			$args['max_value'] = ( $max_value = get_post_meta( $product->get_id(), 'max_qty', true ) ) ? $max_value : 10;;

			$use_stock = get_option( 'min_max_stock' );

			if ( 'yes' === $use_stock && $product->get_manage_stock() ) {
				$product_in_stock = $product->get_stock_quantity();

				if ( $product_in_stock < 10 ) {
					$args['max_value'] = $product_in_stock;
				}
				if ( $product_in_stock == 1 ) {
					$args['min_value'] = 1;
				}
			}

			return $args;
		}

		// variation
		public function variation_settings( $loop, $variation_data, $variation ) {
			woocommerce_wp_text_input( [
				'id'            => 'min_qty[' . $loop . ']',
				'label'         => 'Минимальное кол-во',
				'wrapper_class' => 'form-row',
				'value'         => get_post_meta( $variation->ID, 'min_qty', true ),
				'type'          => 'number',
			] );

			woocommerce_wp_text_input( [
				'id'            => 'max_qty[' . $loop . ']',
				'label'         => 'Максимальное кол-во',
				'wrapper_class' => 'form-row',
				'value'         => get_post_meta( $variation->ID, 'max_qty', true ),
				'type'          => 'number',
			] );
		}
		public function variation_save( $variation_id, $loop ) {

			if ( isset( $_POST['min_qty'][ $loop ] ) ) {
				update_post_meta( $variation_id, 'min_qty', sanitize_text_field( $_POST['min_qty'][ $loop ] ) );
			}
			if ( isset( $_POST['max_qty'][ $loop ] ) ) {
				update_post_meta( $variation_id, 'max_qty', sanitize_text_field( $_POST['max_qty'][ $loop ] ) );
			}
		}


		public function add_section( $sections ) {
			$sections['min_max_qty'] = 'Мин и макс кол-во';

			return $sections;
		}
		public function add_setting( $settings, $current_section ) {
			if ( 'min_max_qty' === $current_section ) {
				$settings = [];

				// заголовок
				$settings[] = [
					'name' => 'Настройки минимального и максимального количества',
					'type' => 'title',
					'desc' => 'Настройки плагина для WooCommerce',
				];

				// текстовое поле
				$settings[] = [
					'name'     => 'Лицензионный ключ',
					'desc_tip' => 'Мы будем учитывать его при получении обновлений',
					'id'       => 'min_max_license_key',
					'type'     => 'text',
				];

				// чекбокс
				$settings[] = [
					'name'     => 'Запасы товара',
					'desc_tip' => 'Учитывать количество товаров на складе при применение ограничений',
					'id'       => 'min_max_stock',
					'type'     => 'checkbox',
					'css'      => 'min-width:300px;',
					'desc'     => 'Учитывать запасы',
				];

				$settings[] = [
					'type' => 'sectionend',
				];
			}

			return $settings;
		}


		public function product_settings() {
			echo '<div class="option_group">';

			woocommerce_wp_text_input( [
				'id'    => 'min_qty',
				'label' => 'Минимальное кол-во',
				'value' => get_post_meta( get_the_ID(), 'min_qty', true ),
				'type'  => 'number',
			] );

			woocommerce_wp_text_input( [
				'id'    => 'max_qty',
				'label' => 'Максимальное кол-во',
				'value' => get_post_meta( get_the_ID(), 'max_qty', true ),
				'type'  => 'number',
			] );

			echo '</div>';
		}
		public function save_product_settings( $post_id ) {
			update_post_meta( $post_id, 'min_qty', sanitize_text_field( $_POST['min_qty'] ) );
			update_post_meta( $post_id, 'max_qty', sanitize_text_field( $_POST['max_qty'] ) );
		}
	}

	new ZshMinMaxQty();
}

