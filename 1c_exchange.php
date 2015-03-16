<?php


session_start();

  if(!empty($_GET['mode'])){
	  if($_GET['mode']== 'checkauth'){
      print "success\n";
      print session_name()."\n";
      print session_id();
	  }
	  
	  if($_GET['mode'] == 'init'){
      print "zip=no\n".
      "file_limit=1000000\n";
	  } 

	  if($_GET['mode'] == 'file'){
		$main_path = '/sites/default/files';
	    $main_path = trim($main_path,'/');
		$path = explode('/',$_GET['filename']);
		array_pop($path);
		if(count($path))
		{
			$cur_dir = $main_path;
			foreach($path as $dir)
			{
				$cur_dir.='/'.$dir;
				if(!file_exists($cur_dir))
					mkdir($cur_dir);
			}
		}
		
	
	    $f = fopen('files/'.$_GET['filename'], 'w+');
      fwrite($f, file_get_contents('php://input'));
      fclose($f);
      print "success\n";
      
      include_once('../../../../includes/bootstrap.inc');  
      drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
      
      $file  = $_SERVER["DOCUMENT_ROOT"].'/sites/all/modules/import1c/files/offers.xml';
      $xml = simplexml_load_file($file);
      foreach($xml->ПакетПредложений->Предложения as $product){
        foreach($product as $item){

          $product_id = db_select('commerce_product', 'c')
            ->fields('c')
            ->condition('c.sku', $item->Ид)
            ->execute()
            ->fetchObject();
            
          if(!empty($product_id->product_id)){ //обновляем товар если он есть
            $tovar = commerce_product_load($product_id->product_id);
            
            
            $form_state = array();
            $form_state['values'] = array();
            $form = array();
            $form['#parents'] = array();
            
            $price = array(LANGUAGE_NONE => array(0 => array(
                  'amount' => $item->Цены->Цена->ЦенаЗаЕдиницу * 100, 
                  'currency_code' => 'RUB',
                )));
            $form_state['values']['commerce_price'] = $price;
            
            field_attach_submit('product', $tovar, $form, $form_state);
            
            commerce_product_save($tovar);
            
          }else{ //добавляем товар если его нет
          
            $form_state = array();
            $form_state['values'] = '';
            $form = array();
            $form['#parents'] = array();
            
            $new_product = commerce_product_new('product');
            
            $new_product->status = 1;
            $new_product->uid = 1;
            $new_product->sku = $item->Ид;
            $new_product->title = $item->Наименование;
            $new_product->type = 'product';
            $new_product->created = $new_product->changed = time();  
            $new_product->language  = LANGUAGE_NONE;
            $new_product->commerce_price['und'][0]['amount'] = $item->Цены->Цена->ЦенаЗаЕдиницу * 100;
            $new_product->commerce_price['und'][0]['currency_code'] = 'RUB';
            $new_product->commerce_price['und'][0]['data']['component'] = array();

            
            field_attach_submit('product', $new_product, $form, $form_state);
           
            commerce_product_save($new_product);
           
           
            $node = new stdClass();
            $node->type = 'product';
            node_object_prepare($node);
            $node->title    = $item->Наименование;
            $node->language = LANGUAGE_NONE;
            $node->field_id['und'][0]['value'] = $item->Ид;
            $node->field_id['und'][0]['safe_value'] = $item->Ид;
            $node->field_id['und'][0]['format'] = NULL;
            $node->body[$node->language][0]['value']   = 'описание товара';
            $node->body[$node->language][0]['summary'] = 'описание товара';
            $node->body[$node->language][0]['format']  = 'filtered_html';  
            $node->field_product['und'][0]['product_id'] = $new_product->product_id;
            $node->uid = 1;
            node_save($node);
          }
        }
      }      
	  } 
	  
	  if($_GET['mode'] == 'import'){
      print "success\n";
	  }
  }
  
  //exit;
?>