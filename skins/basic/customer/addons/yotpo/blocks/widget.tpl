{** block-description:yotpo_widget **}

<div id="yotpo_block_left" class="block">

  <div class="yotpo reviews" 
  	   data-appkey="BWQo8d9IrWIOhGQ0eEihI1ysGkKkXaRjQAU0jlUX"
  	   data-domain="{$config.current_location}"
  	   data-product-id="{$product.product_id}"
  	   data-product-models="1" 
  	   data-name="{$product.product}" 
  	   data-url="{$config.current_location}/{$config.current_url}" 
  	   data-image-url="{$config.current_location}/{$product.main_pair.detailed.http_image_path}" 
  	   data-description="{$product.full_description|unescape|strip_tags|escape:"html"}" 
  	   data-bread-crumbs="{$yotpoBreadCrumbs}"> 
  </div>
</div>