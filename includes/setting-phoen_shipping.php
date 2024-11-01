<?php 

if ( ! defined( 'ABSPATH' ) ) exit;

$settings = array(
								  'title' => array(
									'title' => __( 'Method Title', 'phoen_shipping' ),
									  'type' => 'text',
									  'description' => __( 'Method Title to be display on site', 'phoen_shipping' ),
									  'default' => __( 'Phoen Shipping', 'phoen_shipping' ),
									  ),
								 'tax_status' => array(
									'title' => __( 'Tax Status', 'phoen_shipping' ),
									'desc_tip'        => true,
									  'type' => 'select',
									  'description' => __( 'This controls which shipping method is taxable or none', 'phoen_shipping' ),
									  'default' => __( 'Taxable', 'phoen_shipping' ),
									  'class' => 'phoen_ship_sett',
									  'options' => array(
														'taxable'     => __( 'Taxable', 'phoen_shipping' ),
														'none'      => __( 'None', 'phoen_shipping' ),
													),
										'autoload'=>false,
										'show_if_selected' => 'option',
								 ),
								 
								  'weight_type' => array(
									'title' => __( 'Weight Type', 'phoen_shipping' ),
									'desc_tip'        => true,
									  'type' => 'select',
									  'description' => __( 'This controls which shipping method is taxable or none', 'phoen_shipping' ),
									  'default' => __( 'Use actual weight', 'phoen_shipping' ),
									  'class' => 'phoen_ship_sett',
									  'options' => array(
														'actual_weight'     => __( 'Use actual weight', 'phoen_shipping' ),
														'vol_weight'      => __( 'Use volumetric weight', 'phoen_shipping' ),
														'gmact_vol'     => __( 'Use greater among actual and volumetric weights', 'phoen_shipping' ),
														'smact_vol'      => __( 'Use smaller among actual and volumetric weights', 'phoen_shipping' ),
														
													),
										'autoload'=>false,	
										'show_if_selected' => 'option',
								 ),
								 
								'volumetric_divisor' => array(
									'title' => __( 'Volumetric Divisor', 'phoen_shipping' ),
									'desc_tip'        => true,
									  'type' => 'text',
									  'description' => __( 'It is the volume divisor used to calculate volumetric weight.', 'phoen_shipping' ),
									  'default' => __( '5000', 'phoen_shipping' ),
								),
								
								 'price_type' => array(
									'title' => __( 'Price Type', 'phoen_shipping' ),
									'desc_tip'        => true,
									  'type' => 'select',
									  'description' => __( 'Price Type', 'phoen_shipping' ),
									  'default' => __( 'Tax Inc.', 'phoen_shipping' ),
									  'class' => 'phoen_ship_sett',
									  'options' => array(
														'tax_inc'     => __( 'Tax Inc.', 'phoen_shipping' ),
														'tax_exc'      => __( 'Tax Exc.', 'phoen_shipping' ),
													),
										'autoload'=>false,	
										'show_if_selected' => 'option',
								 ),
								 
								  'visibility' => array(
									'title' => __( 'Visibility', 'phoen_shipping' ),
									  'type' => 'checkbox',
									  'class' => 'phoen_ship_sett',
									  'label'=>__( 'Show only for logged in users', 'phoen_shipping' ),
								 ),
									 
								 'calculation_type' => array(
									'title' => __( 'Calculation Type', 'phoen_shipping' ),
									'desc_tip'        => true,
									  'type' => 'select',
									  'description' => __( 'Calculation Type', 'phoen_shipping' ),
									  'default' => __( 'Per Order', 'phoen_shipping' ),
									  'class' => 'phoen_ship_sett',
									  'options' => array(
														'per_order'     => __( 'Per Order', 'phoen_shipping' ),
													),
										'autoload'=>false,
										'show_if_selected' => 'option',
								 ),	
						);
						
return $settings;

?>