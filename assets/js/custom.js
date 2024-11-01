jQuery(document).ready(function(){
	
	jQuery(".phoen_ship_import_form").hide();
	
	jQuery(window).load(function(){
		jQuery('#phoen_filter_status').val(" ");
		jQuery("#phoen_filter_weight").val();
		jQuery("#phoen_filter_price").val();
		jQuery("#phoen_filter_quantity").val();
		jQuery("#phoen_filter_volume").val();
		jQuery("#phoen_filter_cost").val();
		jQuery("#phoen_filter_pincode").val();
		jQuery("#phoen_filter_comment").val();
		jQuery("#phoen_filter_weight").val();
		jQuery("#phoen_rage_limit").val("5");
	});
/*

---------------------------------------------------------------------  Saving the shipping rate using ajax on click  ------------------------------------------------------------------------------

*/
	jQuery("#phoen_rate_submit").on("click",function(){
		
			var selected=[];
			 jQuery('#phoen_shipping_classes :selected').each(function(){
				 selected[jQuery(this).val()]=jQuery(this).val();
				});
				
			var phoen_rate_weight = jQuery("#phoen_rate_weight").val();
			var phoen_rate_price = jQuery("#phoen_rate_price").val();
			var phoen_rate_quantity = jQuery("#phoen_rate_quantity").val();
			var phoen_rate_volume = jQuery("#phoen_rate_volume").val();
			var phoen_rate_cost = jQuery("#phoen_rate_cost").val();
			var phoen_rate_comment = jQuery("#phoen_rate_comment").val();
			var phoen_rate_status = jQuery("#phoen_rate_status:checked").val();
			var phoen_rate_pincode = jQuery("#phoen_rate_pincode").val();
			
			var ajax_url = jQuery("#ajax_url").val();
			
			function GetURLParameter(sParam)
			{
				var sPageURL = window.location.search.substring(1);
				var sURLVariables = sPageURL.split('&');
				for (var i = 0; i < sURLVariables.length; i++) 
				{
					var sParameterName = sURLVariables[i].split('=');
					if (sParameterName[0] == sParam) 
					{
						return sParameterName[1];
					}
				}
			}
			
			var instance_id = GetURLParameter("instance_id");
			var rate_id = jQuery("#rate_id").val();
			var data = true;
			var redirect_url = jQuery("#location_url").val();
			if(rate_id!=''){
				data = {
						'phoen_selected_class':selected,
						'phoen_rate_weight' :phoen_rate_weight,
						'phoen_rate_price':phoen_rate_price,
						'phoen_rate_quantity':phoen_rate_quantity,
						'phoen_rate_volume':phoen_rate_volume,
						'phoen_rate_cost':phoen_rate_cost,
						'phoen_rate_comment':phoen_rate_comment,
						'phoen_rate_status':phoen_rate_status,
						'phoen_rate_pincode':phoen_rate_pincode,
						'instance_id':instance_id,
						'action':'phoen_shipping_rate',
						'rate_id':rate_id,
					};
				
			}else{
					data = {
						'phoen_selected_class':selected,
						'phoen_rate_weight' :phoen_rate_weight,
						'phoen_rate_price':phoen_rate_price,
						'phoen_rate_quantity':phoen_rate_quantity,
						'phoen_rate_volume':phoen_rate_volume,
						'phoen_rate_cost':phoen_rate_cost,
						'phoen_rate_comment':phoen_rate_comment,
						'phoen_rate_status':phoen_rate_status,
						'phoen_rate_pincode':phoen_rate_pincode,
						'instance_id':instance_id,
						'action':'phoen_shipping_rate',
					};
			}
				jQuery.post(ajax_url,data,function(data){
					
					jQuery('#phoen_shipping_classes').val('');
					jQuery("#phoen_rate_weight").val('');
					jQuery("#phoen_rate_price").val('');
					jQuery("#phoen_rate_quantity").val('');
					jQuery("#phoen_rate_volume").val('');
					jQuery("#phoen_rate_cost").val('');
					jQuery("#phoen_rate_comment").val('');
					jQuery('#phoen_rate_status').prop('checked', false);
					jQuery('#phoen_rate_pincode').val("");
					
					
					 window.location=redirect_url;
					
				});
			
	});
	
/*

----------------------------------------------------------------------  Delete the rate using ajax  ----------------------------------------------------------

*/

	jQuery(".phoen_ship_rate_del").on("click",function(){
		
		var id = jQuery(this).attr('id');
		
		function GetURLParameter(sParam)
			{
				var sPageURL = window.location.search.substring(1);
				var sURLVariables = sPageURL.split('&');
				for (var i = 0; i < sURLVariables.length; i++) 
				{
					var sParameterName = sURLVariables[i].split('=');
					if (sParameterName[0] == sParam) 
					{
						return sParameterName[1];
					}
				}
			}
			
		var instance_id = GetURLParameter("instance_id");
		var redirect_url = jQuery("#location_url").val();
		var data ={
			'action':'phoen_ship_del_rate',
			'id':id,
			'instance_id':instance_id,
		};
		
		jQuery.post(ajax_object.ajax_url,data,function(data){
			jQuery("#rows_"+id).remove();
			window.location=redirect_url;
		});
		
	});
	
/*

------------------------------------------------------------------  Select 2 option shipping_classes  -----------------------------------------------------

*/	


	jQuery("#phoen_shipping_classes").select2();
	
	jQuery("#phoen_ship_clas_filter").select2();
	


/*

----------------------------------------------------------  updating the rate detail on edit click  ----------------------------------------------------------------

*/	

	jQuery(".phoen_edit_ship_rate").on('click',function(){
		
		var id =jQuery(this).attr('id');
		alert(id);
		
	});

// ------------------------------------------------------------------------------ On change shipping classes filter the rate data  ---------------------------------------------------------------------

	jQuery(".phoen_ship_rate_edit").on("click",function(){
		var id = jQuery(this).attr('id');
		var url = window.location.href;
		var rate_url=	window.location.href=url+"&rate_id="+id;
	});
	
//----------------------------------------------------------- Filter rate data using ajax ------------------------------------------------------------------------------------------------------------------------


jQuery(document).on("change keyup click",".phoen_ship_clas_filter",function(event){
			
			event.preventDefault();
			
			var weight ='';
			var ship='';
			var price ='';
			var quantity='';
			var volume='';
			var cost='';
			var comment='';
			var statuse='';
			var data='';
			var value='';
			var paginate='';
			
			
			
			function GetURLParameter(sParam)
			{
				var sPageURL = window.location.search.substring(1);
				var sURLVariables = sPageURL.split('&');
				for (var i = 0; i < sURLVariables.length; i++) 
				{
					var sParameterName = sURLVariables[i].split('=');
					if (sParameterName[0] == sParam) 
					{
						return sParameterName[1];
					}
				}
			}
			
		var instance_id = GetURLParameter("instance_id");
		
		 if(jQuery(this).data('name')==='shipping_paginate'){
				paginate =jQuery(this).attr('id');
			}
		
		jQuery('.phoen_ship_clas_filter').each(function() {
			
            data = jQuery(this).data('name');
			
			value = jQuery(this).val();
			
			if(data==="shipping_limit"){
				limit = value;
			}else if(data==="shipping_class"){
				ship= value;
			}else if(data==="shipping_weight"){
				weight=value;
			}else if(data==="shipping_price"){
				price=value;
			}else if(data==="shipping_quantity"){
				quantity=value;
			}else if(data==="shipping_volume"){
				volume=value;
			}else if(data==="shipping_cost"){
				cost=value;
			}else if(data==="shipping_comment"){
				comment= value;
			}else if(data==="shipping_status"){
				statuse=value;
			}else if(data ==='shipping_pincode'){
				pincode=value;
			}
					
		}); 			
		
	
			var phoen_shipping_filter_rates = jQuery("#pheon_filter_ajax_url").val();
							
			 jQuery.post(

				phoen_shipping_filter_rates,
				{
					'action'    								:  'phoen_shipping_rate_filter',
					'shipping_limit'						:	limit,
					'shipping_class'        				:   ship,
					'shipping_weight'					:	weight,
					'shipping_price'						:	price,
					'shipping_quantity'					:	quantity,
					'shipping_volume'					:	volume,
					'shipping_cost'						:	cost,
					'shipping_comment'				:	comment,
					'shipping_status'					:	statuse,
					'instance_id'							:	instance_id,
					'shipping_pincode'					:	pincode,
					'shipping_paginate'					:	paginate,
				},
				function(response_filter){
					var splitted =response_filter.split('||');
					var filter_rate_details =splitted[0];
					var filter_rate_page =splitted[1];
					var filter_rate_export =splitted[2];
					
					jQuery(".phoen_filter_result").remove();
					jQuery(".phoen_filter_nss").remove();
					//jQuery(".phoen_ship_rate_filter_export").remove();
					jQuery(".phoen_filter_paginate").remove();
					jQuery(".phoen_ship_null_row").remove();
					jQuery(".phoen_page_record_sort").hide();
					jQuery(".phoen_shipping_rate_data").hide();
					jQuery(".phoen_shipping_page_details").hide();
					jQuery(".phoen_page_record").hide();
					//jQuery(".phoen_ship_rate_export").hide();

					if(response_filter.trim()!==' '){
						
						 if((jQuery(filter_rate_details).size())>0){
							 
							jQuery(".phoen_shipping_rate_table tbody .pheon_shipping_add_rate").before(filter_rate_details);
							jQuery(".phoen_shipping_rate_table").after(filter_rate_export);
							
							if((jQuery(".phoen_filter_result").length)>0){
								
								jQuery(".phoen_shipping_rate_table tfoot").append(filter_rate_page);
									
							}
							
						 }else{

							 jQuery(".phoen_shipping_rate_table tbody .pheon_shipping_add_rate").before(filter_rate_details);
							 jQuery(".phoen_shipping_rate_table").after(filter_rate_export);
							 
						 }
							
						
					}else{
						
						jQuery(".phoen_shipping_rate_table tbody .pheon_shipping_add_rate").before("<div class='phoen_filter_nss'><p class='phoen_filter_null'>No rate detail found</p></div>");
						
					}
						
				}
			);
			
		
	
	});
	
	jQuery(".phoen_ship_rate_import").on("click",function(event){
		
		event.preventDefault();
		jQuery(".phoen_ship_import_form").show();
		
	});


	jQuery("#pheon_ssdfsdf_import").on("change",function(e){
		alert('asfsdfsdf');
		
	});
 
});


jQuery(document).ajaxStop(function(){
	
	jQuery(".phoen_ship_rate_edit").on("click",function(){
		
		var id = jQuery(this).attr('id');
		var url = window.location.href;
		var rate_url=	window.location.href=url+"&rate_id="+id;
		
	})
	
	jQuery(".phoen_ship_filter_export").on("click",function(event){
		
		
		event.preventDefault();

	var href = jQuery(this).attr("href");
	// var hostname = jQuery(location).attr("hostname");
	// var url = hostname+"/wp-admin/"+href;
	var data = {
					'action': 'phoen_ship_filter_export',
					'filter_data':filter_data,	
					};
					
					jQuery.post(ajax_filter_rate.ajax_url,data,function(resp){
						if(resp.length>=212){
							window.open(href);
						}else{
							alert("No record found");
						}
					})
	
 });
	
	
})