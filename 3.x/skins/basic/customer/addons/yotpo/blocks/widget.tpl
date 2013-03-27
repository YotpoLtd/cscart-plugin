{** block-description:yotpo_widget **}

  <div class="yotpo reviews" 
  	   data-appkey="{$yotpoAppkey}"
  	   data-domain="http://{$config.http_host}"
  	   data-product-id="{$product.product_id}"
  	   data-product-models="{$product.product_code}"
  	   data-name="{$product.product}" 
       data-url="{$yotpoProductUrl}" 
       data-image-url="{$yotpoProductImageUrl}" 
  	   data-description="{$product.full_description|unescape|strip_tags|escape:"html"}" 
  	   data-bread-crumbs="{$yotpoBreadCrumbs}"
       data-lang="{$yotpoLanguage}"> 
  </div>