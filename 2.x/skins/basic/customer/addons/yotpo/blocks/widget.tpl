{** block-description:yotpo_widget **}

  <div class="yotpo yotpo-main-widget" 
  	   data-product-id="{$product.product_id}"
  	   data-name="{$product.product}" 
       data-url="{$yotpoProductUrl}" 
       data-image-url="{$yotpoProductImageUrl}" 
  	   data-description="{$product.full_description|unescape|strip_tags|escape:"html"}" 
       > 
  </div>
