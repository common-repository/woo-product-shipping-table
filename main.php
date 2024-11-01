<?php 
 /**
 * Plugin Name: Woocommerce Product Shipping Table Rates
 * Plugin URI: https://www.phoeniixx.com/
 * Description: While shopping on any ecommerce store, we often buy items which are delivered from different cities or states to us. Sometimes, this causes us to pay some extra price for them in the form of shipping charges. As for a seller, shipping charges can convert a profitable sales conversion into a loss, if not calculated properly. To avoid this, we created the TABLE RATE SHIPPING PLUGIN. With this plugin, you can set custom shipping charges that will be applied every time an order is placed. This plugin allows the admin to add or remove shipping charges on each product. These charges can be customized depending on the zones where the delivery will take place, the number of items or orders, the total weight of the items or the cart total.
 * Version: 1.0.8
 * Author: phoeniixx
 * Author URI: https://www.phoeniixx.com/
 * License: GPLv2 or later
 * Text Domain: phoen-shipping
 * WC requires at least: 2.6.0
 * WC tested up to: 3.9.0
 */
 
 if ( ! defined( 'ABSPATH' ) ) exit;
 
 
 define( 'PHOEN_BASE_FILE', __FILE__ );
 
 /*
 --------------------------------------------------------------- Cheking if woocommerce is active or not -------------------------------------------------------------------
 */
 
 if(in_array('woocommerce/woocommerce.php',apply_filters('active_plugins',get_option( 'active_plugins' ) ))	){
	 
	 
	 //calling a shipping init function to create custom shipping class
	 
	 function phoen_shipping_create_rate_table(){
		 
			global $wpdb;
			
			$table_name = $wpdb->prefix.'phoen_shipping_table';
			
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				 
				 //table not in database. Create new table
				 
				 $charset_collate = $wpdb->get_charset_collate();
			 
				 $sql = "CREATE TABLE $table_name (
					  id mediumint(9) NOT NULL AUTO_INCREMENT,
					  instance_id int(11) NOT NULL,
					  shipping_class varchar(200) NOT NULL,
					  shipping_weight int(11) DEFAULT '0' NOT NULL,
					  shipping_price int(11) DEFAULT '0' NOT NULL,
					  shipping_quantity int(11) DEFAULT '0' NOT NULL,
					  shipping_volume int(11) DEFAULT '0' NOT NULL,
					  shipping_cost int(11) DEFAULT '0' NOT NULL,
					  shipping_pincode varchar(20) DEFAULT NULL,
					  shipping_comment varchar(200) NOT NULL,
					  shipping_status tinyint(1) DEFAULT NULL,
					  PRIMARY KEY id (id)
				 ) $charset_collate;";
				 
				 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				 dbDelta( $sql );
			}
			
		}
				
	register_activation_hook(__FILE__,'phoen_shipping_create_rate_table');
				
	 
	 add_action("woocommerce_shipping_init","phoen_shipping_method");
	 
		function phoen_shipping_method(){
		
		
			if(!class_exists('Phoen_Shipping_Method')){
				
				
				
				class Phoen_Shipping_Method extends WC_Shipping_Method{
				
					/**	Constructor for Phoen_Shipping_Method
					**		
					**		@access public
					** 	return void
					**/
						
					public function __construct($instance_id = 0){
						
						$this->instance_id 								= absint($instance_id);
						$this->id 												='phoen_shipping';
						$this->method_title								=__('Phoen Shipping','phoen_shipping');
						$this->method_description						=__('Custom Shipping Method for Phoen Shipping','phoen_shipping');
						$this->shipping_methods_option 			= 'phoen_shipping_rate_' . $this->instance_id;
						$this->init();
						$this->enabled 										= isset( $this->settings['enabled'] ) ? $this->settings['enabled']  : '';
						$this->title 											= !empty( $this->get_instance_option('title')) ? $this->get_instance_option('title') : __( 'Phoen Shipping', 'phoen_shipping' );
						$this->supports             						= array(
							'shipping-zones',
							'instance-settings',
						//	'instance-settings-modal',
						);
						
					}
					
					/**
					**	Init your settings
					**
					**	@access public
					**	return void
					**/
					
					function init() {
						
						// Load the settings API
						
						$this->instance_form_fields = include ("includes/setting-phoen_shipping.php");
						
						$this->init_form_fields();
						
						$this->init_settings();
							
						// Save settings in admin if you have any defined
						
						add_action( 'admin_enqueue_scripts', array($this, 'phoen_shipping_admin_scripts'), 10 );
						
						add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
						
					

					}
					
					/**
					** Getting field key of the phoen setting form
					** 
					** @access public
					**	@param $key
					** @return $key of setting fields
					**
					**/
					
					public function get_field_key( $key ) {
						return $this->plugin_id . $this->id . '_' . $key;
					}
						
					/** This function is used to define setting fields for phoen shipping method	
					**
					** @return void
					**/
					
					public	function init_form_fields() { 
						
							$this->form_fields =include("includes/setting-phoen_shipping.php");
							
					}
					
					
					/**
					**This function used to calculate the shipping cost on the basis of phoen shipping method settings
					** @access public
					** @param mixed $package
					**	@return void
					**/
					
					public function calculate_shipping( $package ) {
						
						global $wpdb, $woocommerce;
						
						$table_name = $wpdb->prefix."phoen_shipping_table";
							
							$test =WC_Shipping_Zones::get_zone_matching_package($package);
							$zone_id = $test->get_id();
									
									$tax_status = $this->get_instance_option('tax_status');
									$weight_type = $this->get_instance_option('weight_type');
									$volumetric_divisor = $this->get_instance_option('volumetric_divisor');
									$price_type= $this->get_instance_option('price_type');
									$visibility= $this->get_instance_option('visibility');
									$calculation_type= $this->get_instance_option('calculation_type');
									$shipped_rate = $wpdb->prepare("select * from $table_name where `instance_id`='%d' " ,$this->instance_id);
									$shipped_rate_result1 = $wpdb->get_results($shipped_rate, ARRAY_A);
									$cart_content = $package['contents'];
									$instance_id = $this->instance_id;
									
									
								$cart_details =$woocommerce->cart->get_cart();
								
								$cart_destination = $package['destination'];
								
								$tax_table =$wpdb->prefix."woocommerce_tax_rates";
								
								$location_table = $wpdb->prefix."woocommerce_tax_rate_locations";
								
								$tax_prepare = $wpdb->prepare("SELECT DISTINCT tx.tax_rate ,tx.tax_rate_shipping FROM {$tax_table}  AS tx JOIN {$location_table} AS lt  where tx.tax_rate_country='{$cart_destination['country']}' AND  tx.tax_rate_state='{$cart_destination['state']}'  AND tx.tax_rate_id =lt.tax_rate_id "); 
								
								$tax_result =$wpdb->get_results($tax_prepare,ARRAY_A);
								
								$tax_rate = isset($tax_result[0]['tax_rate'])?(int)$tax_result[0]['tax_rate']:"";
								
								$tax_rate_shipp = isset($tax_result[0]['tax_rate_shipping'])?(int)$tax_result[0]['tax_rate_shipping']:"";
								
								if(count($cart_details)>0){
									$i=0;
									foreach($cart_details as $key =>$cart){
										$product_cart[$i]['quantity'].=$cart_details[$key]['quantity'];
										$product_cart[$i]['weight'].=get_post_meta($cart['product_id'] , '_weight', true);
										$product_cart[$i]['length'].=get_post_meta($cart['product_id'] , '_length', true);
										$product_cart[$i]['width'].=get_post_meta($cart['product_id'] , '_width', true);
										$product_cart[$i]['height'].=get_post_meta($cart['product_id'] , '_height', true);
										$product_cart[$i]['price'].=get_post_meta($cart['product_id'] , '_price', true)*$cart_details[$key]['quantity'];
										$shipping_class_name =$cart['data']->get_shipping_class_id();
										$product_cart[$i]['shipping_class'].=$shipping_class_name;
										$i++;
									}
									
								}						
								
								switch($calculation_type){
										
										case "per_order":
										{
											// calculate the shipping cost on the basis of per order
											
											$weight = $this->calculate_shipping_weight($weight_type,$product_cart,$volumetric_divisor);
											
											
											$price = $this->calculate_shipping_price($price_type,$product_cart,$tax_rate);
											
											$i=0;
											
											// for updating the weight details on the basis of backened 
											
											foreach($product_cart as $key => $prod){
												$product_cart[$i]['weight'] = $weight[$i]['weight']*$product_cart[$i]['quantity'];
												$product_cart[$i]['price'] = $price[$i]['price'];
												$i++;
											}

											
											// combine the detials of all the product to get the shipping cost
											
											foreach($product_cart as $key =>$prod){
												$product_order['quantity'] +=$product_cart[$key]['quantity'];
												$product_order['price'] +=$product_cart[$key]['price'];
												$product_order['weight'] +=$product_cart[$key]['weight'];
											}
											
											if($weight_type =="vol_weight"){
												
												$statement .= " AND ";
												$statement .= isset($product_order['quantity'])&& !empty($product_order['quantity'])?"shipping_quantity=".$product_order['quantity']."":"";
												$statement .= " AND ";
												$statement .= isset($product_order['price'])&& !empty($product_order['price'])?"shipping_price=".$product_order['price']."":"";
												$statement .= " AND ";
												$statement .= isset($product_order['weight'])&& !empty($product_order['weight'])?"shipping_volume=".$product_order['weight']."":"";
												$statement .= " AND ";
												$statement .= "shipping_status=1";
												$order_statement_prepare =$wpdb->prepare("select DISTINCT(instance_id) ,shipping_cost from $table_name where instance_id=$instance_id $statement");
												$order_results=$wpdb->get_results($order_statement_prepare,ARRAY_A);
												
												unset($product_order);
												unset($statement);
												unset($product_cart);
												
											}else{
												
												$statement .= " AND ";
												$statement .= isset($product_order['quantity'])&& !empty($product_order['quantity'])?"shipping_quantity=".$product_order['quantity']."":"";
												$statement .= " AND ";
												$statement .= isset($product_order['price'])&& !empty($product_order['price'])?"shipping_price=".$product_order['price']."":"";
												$statement .= " AND ";
												$statement .= isset($product_order['weight'])&& !empty($product_order['weight'])?"shipping_weight=".$product_order['weight']."":"";
												$statement .= " AND ";
												$statement .= "shipping_status=1";
												$order_statement_prepare =$wpdb->prepare("select DISTINCT(instance_id) ,shipping_cost from $table_name where instance_id=$instance_id $statement");
												$order_results=$wpdb->get_results($order_statement_prepare,ARRAY_A);
												unset($product_order);
												unset($statement);
												unset($product_cart);
												
											}
											
											if($tax_status=="taxable"){
													if(count($order_results)>0){
														if($visibility == "yes"){
															if(is_user_logged_in()){
																foreach($order_results as $key => $order_rate){
																	$rate = array(
																		'id' => $this->get_rate_id(),
																		'label' => $this->title,
																		'cost' => $order_results[$key]['shipping_cost'],
																		'calc_tax'=>'per_order',
																		'package'   => $package,
																	);
																	$this->add_rate( $rate );
																}
															}
														}else{
																foreach($order_results as $key => $order_rate){
																	$rate = array(
																		'id' => $this->get_rate_id(),
																		'label' => $this->title,
																		'cost' => $order_results[$key]['shipping_cost'],
																		'calc_tax'=>'per_order',
																		'package'   => $package,
																	);
																
																$this->add_rate( $rate );
																}
														}
														
													}
											}else{
													if(count($order_results)>0){
														if($visibility == "yes"){
															if(is_user_logged_in()){
																foreach($order_results as $key => $order_rate){
																	$rate = array(
																		'id' => $this->get_rate_id(),
																		'label' => $this->title,
																		'cost' => $order_results[$key]['shipping_cost'],
																		'taxes'     => false,
																		'package'   => $package,
																	);
																	$this->add_rate( $rate );
																
																}
															}
														}else{
																foreach($order_results as $key => $order_rate){
																	$rate = array(
																		'id' => $this->get_rate_id(),
																		'label' => $this->title,
																		'cost' => $order_results[$key]['shipping_cost'],
																		'taxes'     => false,
																		'package'   => $package,
																	);
																	$this->add_rate( $rate );
																
																}
														}
														
													}
											}
											break;
										}
										
									}	
											
						
					//getting the cart details of the product to calculate the shipping cost
					
						
					
						
					 }
					 
					// calculate shipping price on the basis of  price type 

					public function calculate_shipping_price($price_type,$product_cart,$tax_rate){			
					
						switch($price_type){
							case "tax_inc":
							{
								$i=0;
								foreach($product_cart as $kews => $cart_price){
									$product_price[$i]['price'].=$product_cart[$kews]['price'] - round(($product_cart[$kews]['price']*$tax_rate)/100);
									$i++;
								}
								
								return $product_price;
								break;
							}
							case "tax_exc":
							{
								$i=0;
								foreach($product_cart as $kews => $cart_price){
									$product_price[$i]['price'].=$product_cart[$kews]['price'];
									$i++;
								}
								
								return $product_price;
								
								break;
							}
						}
						
					}
					
					//calculate the shipping weight 
					public function calculate_shipping_weight($weight_type, $product_cart,$volumetric_divisor){
						
						switch($weight_type){
							case "actual_weight":
							{
								
								$weights = $this->shipped_weight($product_cart);
								return $weights;
								break;
							}
							case "vol_weight":
							{
								$weights = $this->shipped_volumetric($product_cart,$volumetric_divisor);
								return $weights;
								break;
							}
							case "gmact_vol":
							{
								$weights = $this->shipped_weight_greater($product_cart, $volumetric_divisor);
								return $weights;
								break;
							}
							case "smact_vol":
							{
								$weights = $this->shipped_weight_smaller($product_cart, $volumetric_divisor);
								return $weights;
								break;
							}	
						}
					}
					
					
					public function shipped_weight($product_weights){
						$i=0;
						foreach($product_weights as $keys =>$prod_weight){
								$product_weight[$i]["weight"].=$product_weights[$i]['weight'];
								$i++;
						}
						return $product_weight;
					}
					
					public function shipped_volumetric($product_weights,$volumetric_divisor){
						$i=0;
						foreach($product_weights as $kew =>$prod_weight){
							if(!empty($product_weights[$kew]['length']) && !empty($product_weights[$kew]['width']) && !empty($product_weights[$kew]['height'])) {
								$product_weight[$i]['weight'].=round(($product_weights[$kew]['length'] * $product_weights[$kew]['width'] * $product_weights[$kew]['height'])/$volumetric_divisor);
							}else{
								$product_weight[$i]['weight'].=0;
							}
							$i++;
						}
						 return $product_weight;
					}
					
					
					public function shipped_weight_greater($product_cart, $volumetric_divisor){
						$i=0;
						foreach($product_cart as $kew =>$prod_weight){
							
							$prod_weight = $product_cart[$kew]['weight'];
							
							if(!empty($product_cart[$kew]['length']) && !empty($product_cart[$kew]['width']) && !empty($product_cart[$kew]['height'])) {
								$prod_volumetric=round(($product_cart[$kew]['length'] * $product_cart[$kew]['width'] * $product_cart[$kew]['height'])/$volumetric_divisor);
							}else{
								$prod_volumetric=0;
							}
							
							if($prod_weight>$prod_volumetric){
								$product_weight[$i]['weight'].=$prod_weight;
							}else{
								$product_weight[$i]['weight'].=$prod_volumetric;
							}
							
							$i++;
						}
						
						 return $product_weight;
					}
					
					
					public function shipped_weight_smaller($product_cart, $volumetric_divisor){
						$i=0;
						foreach($product_cart as $kew =>$prod_weight){
							
							$prod_weight = $product_cart[$kew]['weight'];
							if(!empty($product_cart[$kew]['length']) && !empty($product_cart[$kew]['width']) && !empty($product_cart[$kew]['height'])) {
								
								$prod_volumetric=round(($product_cart[$kew]['length'] * $product_cart[$kew]['width'] * $product_cart[$kew]['height'])/$volumetric_divisor);
								
							}else{
								$prod_volumetric=0;
							}
							
							if($prod_weight<$prod_volumetric ){
								$product_weight[$i]['weight'].=$prod_weight;
							}else{
								$product_weight[$i]['weight'].=$prod_volumetric;
							}
							
							$i++;
						}
						
						 return $product_weight;
					}
					
						
					public function 	phoen_shipping_admin_scripts(){
						
						wp_enqueue_script('phoen-shipping-custom',plugin_dir_url(__FILE__).'assets/js/custom.js',array('jquery'),false,true);
						
						wp_enqueue_style("phoen-shipping-style",plugin_dir_url(__FILE__).'assets/css/phoen-shipping-style.css');
						
						wp_localize_script(  'phoen-shipping-custom', 'ajax_object', 
						array( 'ajax_url' => admin_url( 'admin-ajax.php' ),'id'=>0));
						
						wp_localize_script( 'phoen-shipping-custom', 'phoen_rate_limit',
						array( 'ajax_url' => admin_url( 'admin-ajax.php' ),'id'=>0) );
						
						wp_localize_script( 'phoen-shipping-custom', 'phoen_ship_rate_filter',
						array( 'ajax_url' => admin_url( 'admin-ajax.php' ),'id'=>0) );
						
						wp_localize_script( 'phoen-shipping-custom', 'ajax_filter_rate',
						array( 'ajax_url' => admin_url( 'admin-ajax.php' ),'id'=>0) );
						
					}
						
	/*

		------------------------------------------------------------------------------------ saving the settings of shipping methods.....  --------------------------------------------------------------------------
		
	*/
						
					public function admin_options() {
						 ?>
							 <h2><?php _e('Rate Table','woocommerce'); ?></h2>
							 <table class="form-table">
							 <?php $this->generate_settings_html(); ?>
							 </table> <?php
						 }
						
						
				
					public function generate_settings_html( $form_fields = array(), $echo = true ) 
					{
							global $wpdb;
							
							$instance_id = $this->instance_id;
							
							$html=null;
							
							$table_name = $wpdb->prefix."phoen_shipping_table";
							$ajax_url = admin_url('admin-ajax.php') ;
							
							$page = ! empty($_GET['ratepage'] ) ? (int) sanitize_text_field($_GET['ratepage']) : 1;
							
							$shipping_rate_details1 = $wpdb->prepare("select shipping_class,shipping_weight,shipping_price,shipping_quantity,shipping_volume,shipping_cost,shipping_comment,shipping_status,shipping_pincode from $table_name where `instance_id`='%d' " ,$instance_id);
							$shipping_rate_result1 = $wpdb->get_results($shipping_rate_details1, ARRAY_A);
							$i=0;
							foreach($shipping_rate_result1 as $key =>$shiping_rate_data){
								$shiped_class = explode(',',$shipping_rate_result1[$key]['shipping_class']);
								foreach($shiped_class as $clas_ss){
									$shipping_rate_resultsdds = get_term_by('id',$clas_ss,'product_shipping_class',ARRAY_A);
									$shipping_names.= $shipping_rate_resultsdds['name']." , ";
								}
									$shipping_rate_data[$i]['shipping_class'] .=substr($shipping_names,0,-2);
									unset($shipping_names);
									$shipping_rate_data[$i]['shipping_weight'] .=$shipping_rate_result1[$key]['shipping_weight'];
									$shipping_rate_data[$i]['shipping_price'] .=$shipping_rate_result1[$key]['shipping_price'];
									$shipping_rate_data[$i]['shipping_quantity'] .=$shipping_rate_result1[$key]['shipping_quantity'];
									$shipping_rate_data[$i]['shipping_volume'] .=$shipping_rate_result1[$key]['shipping_volume'];
									$shipping_rate_data[$i]['shipping_cost'] .=$shipping_rate_result1[$key]['shipping_cost'];
									$shipping_rate_data[$i]['shipping_pincode'] .=$shipping_rate_result1[$key]['shipping_pincode'];
									
									$shipping_rate_data[$i]['shipping_comment'] .=$shipping_rate_result1[$key]['shipping_comment'];
									
									if($shipping_rate_result1[$key]['shipping_status']==1){
										$shipping_rate_data[$i]['shipping_status'] .='Active';
									}else{
										$shipping_rate_data[$i]['shipping_status'] .='InActive';
									}
								
								$i++;
							}
							
							$total = count( $shipping_rate_result1); //total items in array 
							$limit =5;
							$totalPages = ceil( $total/ $limit ); //calculate total pages
							$page = max($page, 1); //get 1 page when $_GET['page'] <= 0
							$page = min($page, $totalPages); //get last page when $_GET['page'] > $totalPages
							$offset = ($page - 1) * $limit;
							$upper = min( $total, $page * $limit);
							$lower = $total==0 ?0:($page - 1) * $limit + 1;
							if( $offset < 0 ) $offset = 0;
							
							$shipping_rate_details = $wpdb->prepare("select * from $table_name where `instance_id`='%d' LIMIT $limit OFFSET $offset " ,$instance_id);
							$shipping_rate_result = $wpdb->get_results($shipping_rate_details, ARRAY_A);
							
							$fields =$this->get_field_key($key);
							
							if ( empty( $form_fields ) ) {
							  $form_fields = $this->get_form_fields();
							}

							$html = '';
							foreach ( $form_fields as $k => $v ) {
							  $type = $this->get_field_type( $v );

							  if ( method_exists( $this, 'generate_' . $type . '_html' ) ) {
								$html .= $this->{'generate_' . $type . '_html'}( $k, $v );
							  } else {
								$html .= $this->generate_text_html( $k, $v );
							  }
							}
							
							 $shipping_classes = WC()->shipping()->get_shipping_classes(); 
							
							$paginate =paginate_links(array(
																			'base' => add_query_arg( 'ratepage', '%#%' ),
																			'format' => '',
																			'prev_text' => __(''),
																			'next_text' => __(''),
																			'total' => $totalPages,// like 10 items per page
																			'current' => $page
																		));
							
							$url = $_SERVER;
							$redirect_url=null;
							$rate_url=null;
							$rate_data = null;
							$redirect_url.= $url['SCRIPT_URI'].'?page=wc-settings&tab=shipping&instance_id='.$this->instance_id;
							$rate_url .=$url['QUERY_STRING'];
							
								if(!empty($shipping_rate_result)){
									
									foreach($shipping_rate_result as $key =>$shipping){
										
										$rate_data.="<tr id='rows_".$shipping['id']."' class='phoen_shipping_rate_data'>";
										$rate_data.="<td class='shiping-class'>";
										
										if(!empty($shipping['shipping_class'])){
											$shipping_names=null;
												$shipping_classes1 = explode(',',$shipping['shipping_class']);
													foreach($shipping_classes1 as $keys =>$ship_name){
														
														$shipping_rate_result = get_term_by('id',$ship_name,'product_shipping_class',ARRAY_A);
														
														$shipping_names.= $shipping_rate_result['name']." , ";
														
													}
													$rate_data .=substr($shipping_names,0,-2);
													unset($shipping_names);
										}
										
										$rate_data.="</td>";
										$rate_data.="<td class='shiping-weight'>".$shipping['shipping_weight']."</td>
															<td class='shiping-price'>". $shipping['shipping_price']."</td>
															<td class='shiping-quantity'>". $shipping['shipping_quantity']."</td>
															<td class='shiping-volume'>". $shipping['shipping_volume']."</td>
															<td class='shiping-cost'>".$shipping['shipping_cost']."</td>
															<td class='shiping-pincode'>".$shipping['shipping_pincode']."</td>
															<td class='shiping-comment'>".$shipping['shipping_comment']."</td>";
									$rate_active =($shipping['shipping_status']==1)?"<span class='status_active'>Active</span>":"<span class='status_inactive'>InActive</span>";
									$rate_data .="<td class='shiping-status'>".$rate_active."</td>";
									$rate_data.=	"<td class='shiping-actions'>
																	<input type='button'  value='Edit' id='".$shipping['id']."' class='phoen_ship_rate_edit' />
																	<input type='button' id='".$shipping['id']."' class='phoen_ship_rate_del'  value='Delete'/>
																</td>
														</tr>";
									}
										
								}
								
								$ship_id_rate=array(
										'shipping_class' =>array(),
										'shipping_weight'=>'',
										'shipping_price'=>'',
										'shipping_quantity'=>'',
										'shipping_volume'=>'',
										'shipping_cost'=>'',
										'shipping_comment'=>'',
										'shipping_status'=>''
									);
								if(isset($_GET['rate_id'])){
									
									$rate_id = sanitize_text_field($_GET['rate_id']);
									$rate_query = $wpdb->prepare("select * from $table_name where `instance_id`='%d' AND `id`='%d' ",$instance_id,$rate_id);
									$rate_details = $wpdb->get_results($rate_query, ARRAY_A);
									if(!empty($rate_details)){
										foreach($rate_details  as $keys  =>$ship){
											$ship_cls = explode(',' ,$rate_details[$keys]['shipping_class']);
												$i=0;
												if(!empty($ship_cls)){
													foreach($ship_cls as $clas){
														$ship_id_rate['shipping_class'][$i].=@$clas;
														$i++;
													}
												}
											
											$ship_id_rate['shipping_weight'] .=$rate_details[$keys]['shipping_weight'];
											$ship_id_rate['shipping_price'] .=$rate_details[$keys]['shipping_price'];
											$ship_id_rate['shipping_quantity'] .=$rate_details[$keys]['shipping_quantity'];
											$ship_id_rate['shipping_volume'] .=$rate_details[$keys]['shipping_volume'];
											$ship_id_rate['shipping_cost'] .=$rate_details[$keys]['shipping_cost'];
											$ship_id_rate['shipping_comment'] .=$rate_details[$keys]['shipping_comment'];
											$ship_id_rate['shipping_pincode'] .=$rate_details[$keys]['shipping_pincode'];
											$ship_id_rate['shipping_status'] .=$rate_details[$keys]['shipping_status'];
										}
									}
								}else{
									$rate_id='';
								}
								$shiping_class_data = "<select id='phoen_shipping_classes' name='phoen_shipping_classes[]' class ='phoen_shipping_classes' multiple>";
																	foreach($shipping_classes as $key =>$ship_class)
																	{
																		$selected_classes  = !empty($ship_id_rate['shipping_class']) && in_array($ship_class->term_id,$ship_id_rate['shipping_class'])?'selected="selected"':'';
																		$shiping_class_data	.="<option value=" .$ship_class->term_id  ." ".$selected_classes."> ".$ship_class->name ."</option>";
													
																	}
																		
																	$shiping_class_data.="</select>";
																	
								$selected_filter_class="<select id='phoen_ship_clas_filter' name='phoen_ship_clas_filter[]' class ='phoen_ship_clas_filter' data-name='shipping_class' multiple>";
																	foreach($shipping_classes as $key =>$ship_class1){
																			$selected_filter_class	.="<option value=" .$ship_class1->term_id  ." > ".$ship_class1->name ."</option>";
																	}		
								$selected_filter_class.="</select>";
																
									$html.="<tr >
												 <table class='phoen_shipping_rate_table'>
													 <div class='phoen_table_range'>
														 <label for='phoen_rage_limit'>". __('Items per page : ','phoen-shipping')."</label>
														 <select id='phoen_rage_limit' name='phoen_rage_limit' class='phoen_ship_clas_filter phoen_ship_sett' data-name='shipping_limit'>
															 <option value='5' ".((isset($limit)&& ($limit==5))?'selected=selected':'').">5</option>
															 <option value='10'  ".((isset($limit)&& ($limit==10))?'selected=selected':'').">10</option>
															 <option value='25'  ".((isset($limit)&& ($limit==25))?'selected=selected':'').">25</option>
															 <option value='50'  ".((isset($limit)&& ($limit==50))?'selected=selected':'').">50</option>
															 <option value='75'  ".((isset($limit)&& ($limit==75))?'selected=selected':'').">75</option>
															 <option value='100' ".((isset($limit)&& ($limit==100))?'selected=selected':'').">100</option>
														 </select>
													</div>
													<h2 class='phoen_shipping_heading'>". __('Shipping Rate','phoen-shipping')."</h2>
													 <thead>
														<tr>
															<td colspan='10' class='phoen_table_limit'>". __('Limits','phoen-shipping')."</td>
														</tr>
														
														 <tr role='rows' id='phoen_filter_row'>
														<input type='hidden' name='ajax_url' value='".$ajax_url."' id='pheon_filter_ajax_url'>														 
														<td class='phoen-column shiping-class'>
															
																".$selected_filter_class."
														</td>
														<td class='phoen-column shiping-weight'><input type='text' name='phoen_filter_weight' id='phoen_filter_weight' data-name='shipping_weight' class ='phoen_ship_clas_filter' /></td>
														<td class='phoen-column shiping-price'><input type='text' name='phoen_filter_price' id='phoen_filter_price' data-name='shipping_price'  class ='phoen_ship_clas_filter'/></td>
														<td class='phoen-column shiping-quantity'><input type='text' name='phoen_filter_quantity' id='phoen_filter_quantity'  data-name='shipping_quantity' class ='phoen_ship_clas_filter'/></td>
														<td class='phoen-column shiping-volume'><input type='text' name='phoen_filter_volume' id='phoen_filter_volume' data-name='shipping_volume' class ='phoen_ship_clas_filter'/></td>
														<td class='phoen-column shiping-cost'><input type='text' name='phoen_filter_cost' id='phoen_filter_cost'  data-name='shipping_cost' class ='phoen_ship_clas_filter'/></td>
														<td class='phoen-column shiping-pincode'><input type='text' name='phoen_filter_pincode' id='phoen_filter_pincode'  data-name='shipping_pincode' class ='phoen_ship_clas_filter'/></td>
														<td class='phoen-column shiping-comment'><input type='text' name='phoen_filter_comment' id='phoen_filter_comment'  data-name='shipping_comment' class ='phoen_ship_clas_filter'/></td>
														<td class='phoen-column shiping-status'>
														
															<select id='phoen_filter_status' name='phoen_filter_status' data-name='shipping_status' class='phoen_ship_clas_filter'>
																<option value=''></option>
																<option value='1'>Active</option>
																<option value='0'>InActive</option>
															</select>
														</td>
														<td class='phoen-column shiping-actions'><?php _e('Filters','phoen-shipping');?></td>
													</tr>
														 <tr role='rows'>
															 <td class='phoen-column shiping-class'>". __('Shipping Class','phoen-shipping')."</td>
															 <td class='phoen-column shiping-weight'>". __('Weight','phoen-shipping')."</td>
															 <td class='phoen-column shiping-price'>". __('Price','phoen-shipping')."</td>
															 <td class='phoen-column shiping-quantity'>". __('Quantity','phoen-shipping')."</td>
															 <td class='phoen-column shiping-volume'>".__('Volume','phoen-shipping')."</td>
															 <td class='phoen-column shiping-cost'>". __('Cost','phoen-shipping')."</td>
															 <td class='phoen-column shiping-pincode'>". __('Pincode','phoen-shipping')."</td>
															 <td class='phoen-column shiping-comment'>". __('Comment','phoen-shipping')."</td>
															 <td class='phoen-column shiping-status'>". __('Status','phoen-shipping')."</td>
															 <td class='phoen-column shiping-actions'>". __('Actions ','phoen-shipping')."</td>
														 </tr>
													 </thead>
													 <tbody >
														 ".$rate_data."
														<tr role='rows' class='pheon_shipping_add_rate'>
																<td >".$shiping_class_data."
																</td>
																
															<td ><input type='text' name='phoen_rate_weight' id='phoen_rate_weight'  value ='".(isset($ship_id_rate['shipping_weight'])?$ship_id_rate['shipping_weight']:'')."' /></td>
																<td ><input type='text' name='phoen_rate_price' id='phoen_rate_price'  value ='".(isset($ship_id_rate['shipping_price'])?$ship_id_rate['shipping_price']:'')."' /></td>
																<td ><input type='text' name='phoen_rate_quantity' id='phoen_rate_quantity'  value ='".(isset($ship_id_rate['shipping_quantity'])?$ship_id_rate['shipping_quantity']:'')."' /></td>
																<td ><input type='text' name='phoen_shipping_volume' id='phoen_rate_volume'  value ='".(isset($ship_id_rate['shipping_volume'])?$ship_id_rate['shipping_volume']:'')."' /></td>
																<td ><input type='text' name='phoen_rate_cost' id='phoen_rate_cost'  value ='".(isset($ship_id_rate['shipping_cost'])?$ship_id_rate['shipping_cost']:'')."' /></td>
																<td ><input type='text' name='phoen_rate_pincode' id='phoen_rate_pincode'  value ='".(isset($ship_id_rate['shipping_pincode'])?$ship_id_rate['shipping_pincode']:'')."' /></td>
																<td ><input type='text' name='phoen_rate_comment' id='phoen_rate_comment'  value ='".(isset($ship_id_rate['shipping_comment'])?$ship_id_rate['shipping_comment']:'')."' /></td>
																<td ><input type='checkbox' name='phoen_rate_status' id='phoen_rate_status' value='1' ".(isset($ship_id_rate['shipping_status'])&& ($ship_id_rate['shipping_status']==1)?'checked=checked':'')."   /></td>
																<td ><input type='button' name='phoen_rate_submit' value='save'  id='phoen_rate_submit' /></td>
																<input type='hidden' name='ajax_url' id='ajax_url' value='". admin_url('admin-ajax.php')."' />
																<input type='hidden' name='location_url' value='". $redirect_url."' id='location_url' />
																<input type='hidden' name='rate_id' id='rate_id' value='".$rate_id."' />
														</tr>
													 </tbody>
													 <tfoot>
														<tr class='phoen_shipping_page_details'>
														<td colspan='2'>". 'Showing '.$lower.' to '.$upper.' out of '.$total.' rates'."</td>
															<td colspan='7'>
																".$paginate."
															</td>
														</tr>
													</tfoot>
												</table>
											</tr>
													"; 
												 
												 
													$phoen_ship_file = fopen('phoen_shipping_rate.csv', 'w');
												                                        
                                                        fputcsv($phoen_ship_file, array('Shipping Class','Shipping Weight','Shipping Price','Shipping Quantity','Shipping Volume','Shipping Cost','Shipping Pincode','Shipping Comment','Shipping Status'));
                                                         if(count($shipping_rate_data)>0){
															foreach ($shipping_rate_data as $key => $shipping_rate_datasss)
															{
																
																	fputcsv($phoen_ship_file, $shipping_rate_datasss);
															}
															
															 fclose($phoen_ship_file);
														 }
																								
												 
							if ( $echo ) {
							  echo $html;
							} else {
							  return $html;
							}
							
					}
				
					
					
				}
				
			}
		}
	
		
/*
	-------------------------------------------------------------------- Adding custom woocommerce shipping methods ------------------------------------------------
*/
	
		function add_phoen_shipping_method( $methods ) {
			
				$methods['phoen_shipping'] = 'Phoen_Shipping_Method';
				return $methods;
					
			}
 
    add_filter( 'woocommerce_shipping_methods', 'add_phoen_shipping_method',10,1 );
	
	
						if(is_admin()){
							
							add_action('wp_ajax_phoen_shipping_rate','phoen_shipping_rate');
							
							add_action('wp_ajax_phoen_ship_del_rate','phoen_ship_del_rate');
							
							
							// add_action('wp_ajax_phoen_rate_limit','phoen_rate_limit');
							
							add_action('wp_ajax_phoen_shipping_rate_filter','phoen_shipping_rate_filter');
							add_action('wp_ajax_phoen_ship_filter_export','phoen_ship_filter_export');
							
						}

/*
----------------------------------------------------------------  Saving phoen table rate using ajax  ------------------------------------------------------------------------

*/
						function phoen_shipping_rate()
						{							
							global $wpdb;
						
							$shipping_rate_table = $wpdb->prefix."phoen_shipping_table";
							$phoen_selected_class = isset($_POST['phoen_selected_class'])?($_POST['phoen_selected_class']):'';
							$phoen_selected_class =array_values(array_filter($phoen_selected_class));
							$phoen_selected_class = implode(',',$phoen_selected_class);
							$phoen_rate_weight = isset($_POST['phoen_rate_weight'])?sanitize_text_field($_POST['phoen_rate_weight']):'';
							$phoen_rate_price = isset($_POST['phoen_rate_price'])?sanitize_text_field($_POST['phoen_rate_price']):'';
							$phoen_rate_quantity = isset($_POST['phoen_rate_quantity'])?sanitize_text_field($_POST['phoen_rate_quantity']):'';
							$phoen_rate_volume = isset($_POST['phoen_rate_volume'])?sanitize_text_field($_POST['phoen_rate_volume']):'';
							$phoen_rate_cost = isset($_POST['phoen_rate_cost'])?sanitize_text_field($_POST['phoen_rate_cost']):'';
							$phoen_rate_comment = isset($_POST['phoen_rate_comment'])?sanitize_text_field($_POST['phoen_rate_comment']):'';
							$phoen_rate_status = isset($_POST['phoen_rate_status'])?sanitize_text_field($_POST['phoen_rate_status']):'';	
							$phoen_rate_pincode = isset($_POST['phoen_rate_pincode'])?sanitize_text_field($_POST['phoen_rate_pincode']):'';	
							
							$instance_id = isset($_POST['instance_id'])?sanitize_text_field($_POST['instance_id']):'';
							$rate_id = isset($_POST['rate_id'])?sanitize_text_field($_POST['rate_id']):'';
							
							if($rate_id){
								$wpdb->query(
								$wpdb->prepare(
											"UPDATE $shipping_rate_table SET 
											shipping_class='$phoen_selected_class',
											shipping_weight='$phoen_rate_weight',
											shipping_price='$phoen_rate_price',
											shipping_quantity='$phoen_rate_quantity',
											shipping_volume='$phoen_rate_volume',
											shipping_cost='$phoen_rate_cost',
											shipping_comment='$phoen_rate_comment',
											shipping_status='$phoen_rate_status',
											shipping_pincode='$phoen_rate_pincode'
											WHERE id=$rate_id AND instance_id=$instance_id"
										));
								
							}else{
												
								$wpdb->query("INSERT INTO $shipping_rate_table (id,instance_id,shipping_class,shipping_weight,shipping_price,shipping_quantity,shipping_volume,shipping_cost,shipping_comment,shipping_status,shipping_pincode) 
								VALUES ( NULL,'$instance_id','$phoen_selected_class', '$phoen_rate_weight', '$phoen_rate_price', '$phoen_rate_quantity', '$phoen_rate_volume', '$phoen_rate_cost', '$phoen_rate_comment', 
												'$phoen_rate_status', '$phoen_rate_pincode')" );
											
								
							}
							
							die();
							
						}
						
						
						function phoen_ship_del_rate(){
							global $wpdb;
							$table_name = $wpdb->prefix."phoen_shipping_table";
							$rate_id = isset($_POST['id'])?sanitize_text_field($_POST['id']):'';
							$instance_id  = isset($_POST['instance_id'])?sanitize_text_field($_POST['instance_id']):'';
							$wpdb->query(
											  'DELETE  FROM '.$table_name.'
											   WHERE id = "'.$rate_id.'" AND instance_id="'.$instance_id.'"'
											);
							die();
						}
						
						
						function phoen_shipping_rate_filter(){
							
							global $wpdb;
							
							$table_name = $wpdb->prefix.'phoen_shipping_table';
							
							$instance_id= isset($_POST['instance_id'])?sanitize_text_field($_POST['instance_id']):'';
							
							if(isset($_POST['shipping_class'])&& !empty($_POST['shipping_class'])){
									// $post_id_str = array_fill( 0, count( $_POST['shipping_class'] ), '%s' );
									// $in_str = join( ',', $post_id_str);
								$shipping_class = implode( ',', array_map( 'absint', $_POST['shipping_class'] ) );
								$stam.= " AND  `shipping_class` in ($shipping_class)";
							}
							
							if(isset($_POST['shipping_weight'])&& !empty($_POST['shipping_weight'])){
								$shipping_weight = $_POST['shipping_weight'];
								$stam.= " AND  shipping_weight='$shipping_weight'";
							}
							
							if(isset($_POST['shipping_price']) && !empty($_POST['shipping_price'])){
								$shipping_price = $_POST['shipping_price'];
								$stam.= " AND  shipping_price='$shipping_price'";
							}
							
							if(isset($_POST['shipping_quantity']) && !empty($_POST['shipping_quantity'])){
								$shipping_quantity = $_POST['shipping_quantity'];
								$stam.= " AND  shipping_quantity='$shipping_quantity'";
							}
							
							if(isset($_POST['shipping_volume']) && !empty($_POST['shipping_volume'])){
								$shipping_volume = $_POST['shipping_volume'];
								$stam.= " AND  shipping_volume='$shipping_volume'";
							}
							
							if(isset($_POST['shipping_cost']) && !empty($_POST['shipping_cost'])){
								$shipping_cost = $_POST['shipping_cost'];
								$stam.= " AND  shipping_cost='$shipping_cost'";
							}
							
							if(isset($_POST['shipping_comment']) && !empty($_POST['shipping_comment'])){
								$shipping_comment = $_POST['shipping_comment'];
								$stam.= " AND  shipping_comment='$shipping_comment'";
							}
							
						
							
							if(isset($_POST['shipping_status']) && ($_POST['shipping_status']=='1')){
								$shipping_status = $_POST['shipping_status'];
								$stam.= " AND  shipping_status='$shipping_status'";
							}else if($_POST['shipping_status']=='0'){
								$stam.= " AND  shipping_status='0'";
							}
							
								if(isset($_POST['shipping_pincode']) && !empty($_POST['shipping_pincode'])){
								$shipping_pincode = $_POST['shipping_pincode'];
								$stam.= " AND  shipping_pincode='$shipping_pincode'";
							}
							
							if(isset($_POST['shipping_paginate']) && !empty($_POST['shipping_paginate'])){
								$paginates = $_POST['shipping_paginate'];
							}else{
								$paginates=1;
							}
							
							
							$query_prepare =$wpdb->prepare("select * from $table_name where instance_id =$instance_id $stam");
							
							$query_result1=$wpdb->get_results($query_prepare,ARRAY_A);					
							
							if(count($query_result1)>0 && $query_result1!==null){
								
								$total = count($query_result1);
									$page =$paginates;
									$limit =isset($_POST['shipping_limit'])?$_POST['shipping_limit']:'';
									$totalPages = ceil( $total/ $limit ); //calculate total pages
									$page = max($page, 1); //get 1 page when $_GET['page'] <= 0
									$page = min($page, $totalPages); //get last page when $_GET['page'] > $totalPages
									$offset = ($page - 1) * $limit;
									$upper = min( $total, $page * $limit);
									$lower = ($page - 1) * $limit + 1;
									if( $offset == 0 ) $offset = 0;
									
								if(isset($_POST['shipping_limit']) && !empty($_POST['shipping_limit'])){
									
									$stam .=" LIMIT $limit OFFSET $offset ";
									
								}else{
									$limit=5;
									$stam .=" LIMIT $limit OFFSET $offset ";
								}
								
								$query_prepare =$wpdb->prepare("select * from $table_name where instance_id =$instance_id $stam");
								$query_result=$wpdb->get_results($query_prepare,ARRAY_A);
								
								
								$response_filter = array();
								if(count($query_result)>0){
									foreach($query_result as $keyss =>$filter_result){
										if(!empty($query_result[$keyss]['shipping_class'])){
												$shipping_names=null;
													$shipping_classes1 = explode(',',$query_result[$keyss]['shipping_class']);
														foreach($shipping_classes1 as $keys =>$ship_name){
															
															$shipping_rate_result = get_term_by('id',$ship_name,'product_shipping_class',ARRAY_A);
															
															$shipping_names.= $shipping_rate_result['name']." , ";
															
														}
														$shipping_class_name =substr($shipping_names,0,-2);
														unset($shipping_names);
											}
										
										$resp.="<tr class='phoen_filter_result' id='rows_".$query_result[$keyss]['id']."'>";
										
										if($query_result[$keyss]['shipping_status']==1){
											$ship_status = "<span class='status_active'>Active</span>";
										}else{
											$ship_status = "<span class='status_inactive'>InActive</span>";
										}
										$response =array();
										$resp.="<td class='shiping-class'>";
										$resp.=$shipping_class_name;
										$resp.="</td>";
										$resp.="<td class='shiping-weight'>";
										$resp.=$query_result[$keyss]['shipping_weight'];
										$resp.="</td>";
										$resp.="<td class='shiping-price'>";
										$resp.=$query_result[$keyss]['shipping_price'];
										$resp.="</td>";
										$resp.="<td class='shiping-quantity'>";
										$resp.=$query_result[$keyss]['shipping_quantity'];
										$resp.="</td>";
										$resp.="<td class='shiping-volume'>";
										$resp.=$query_result[$keyss]['shipping_volume'];
										$resp.="</td>";
										$resp.="<td class='shiping-cost'>";
										$resp.=$query_result[$keyss]['shipping_cost'];
										$resp.="</td>";
										$resp.="<td class='shiping-pincode'>";
										$resp.=$query_result[$keyss]['shipping_pincode'];
										$resp.="</td>";
										$resp.="<td class='shiping-comment'>";
										$resp.=$query_result[$keyss]['shipping_comment'];
										$resp.="</td>";
										$resp.="<td class='shiping-status'>";
										$resp.=$ship_status;
										$resp.="</td>";
										$resp.="<td class='shiping-actions'>";
										$resp.="<input type='button'  value='Edit' id='".$query_result[$keyss]['id']."' class='phoen_ship_rate_edit' />";
										$resp.="<input type='button' id='".$query_result[$keyss]['id']."' class='phoen_ship_rate_del'  value='Delete'/>";
										$resp.="</td>";
										$resp.="</tr>";
									}
									// $resp .= "<input type='hidden' name='phoen_ship_filter'  id='phoen_ship_filter' value='".json_encode($query_result1)."'/>";
								}
							}else{
								$resp="<tr class='phoen_ship_null_row'><td colspan='9'><div class='phoen_filter_nss'><p class='phoen_filter_null'>No rate detail found</p></div></td></tr>";
							}

							for ($i=1; $i<=$totalPages; $i++) { 
								if($page==$i){
									$paginates1 .="<a href='admin.php?page=wc-settings&tab=shipping&instance_id=".$instance_id."' class='phoen_ship_clas_filter active' id='".$i."' data-name='shipping_paginate' onclick='event.preventDefault();' >".$i."</a> "; 
								}else{
									$paginates1 .="<a href='admin.php?page=wc-settings&tab=shipping&instance_id=".$instance_id."' class='phoen_ship_clas_filter' id='".$i."' data-name='shipping_paginate' onclick='event.preventDefault();' >".$i."</a> "; 
									}
								

							}
							
									$split =" || ";
									$resp_paginate.="<tr class='phoen_filter_paginate'>";
									$resp_paginate.="<td colspan='2'><p>". 'Showing '.$lower.' to '.$upper.' out of '.$total.' rates'."</p></td>";
									$resp_paginate.="<td colspan='7' >";
									$resp_paginate.=$paginates1;
									$resp_paginate.="</td>";
									$resp_paginate.="</tr>";
									
									$response_filter = array("0"=>$resp ,"1"=> $split,"2"=>$resp_paginate);
									
								
									
									echo stripslashes(json_encode($response_filter));
									
									
										?>
							
							<script >
								var filter_data = "";
								filter_data = <?php echo json_encode($query_result1);?>;
								
							</script >
							<?php
							
							die();
						}
						
	
// function to check whether the free shipping is available on zone then only show the free shipping  method 	
	
		function phoen_shipping_hide_on_free( $rates ) {
			$free = array();
			foreach ( $rates as $rate_id => $rate ) {
				if ( 'free_shipping' === $rate->method_id ) {
					$free[ $rate_id ] = $rate;
					break;
				}
			}
			return ! empty( $free ) ? $free : $rates;
		}
add_filter( 'woocommerce_package_rates', 'phoen_shipping_hide_on_free', 100 );
	
 }
?>
