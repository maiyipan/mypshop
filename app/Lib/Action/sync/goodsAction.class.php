<?php

/**
 * 同步菜单
 * */
class goodsAction extends Action {
  const SHOPID = '0003';
  public function index() {
    // $this->getGoodsInfo(goodsAction::SHOPID);
    // $this->getBrandInfo(goodsAction::SHOPID);
    $this->updateInventory ( goodsAction::SHOPID );
  }
  
  /**
   * 获取商品信息
   *
   * @param string $shopId          
   */
  public function getGoodsInfo() {
    $length = 100;
    $base = 100;
    $shop = M ( 'shop' );
    dump ( $length % $base != $base );
    while ( $length % $base != $base ) {
      $result = M ( 'sdwx_goods', null, 'DB_CONFIG1' )->field ( '*' )
        ->where ( "STATUS=%d", array (0 ) )->limit ( $base )
        ->order ( 'SHEETID' )->select ();
      $length = count ( $result );
      //Log::write ( 'the result length:' . $length );
      if (empty ( $result )) {
        return;
      }
      
      foreach ( $result as $val ) {
        $temp = ($val ['FRESHFLAG'] == 0) ? '常温' : '低温';
        $goodsSdec = $temp . '保存，保质期：' . $val ['KEEPDAY'] 
                  . '天，保鲜期：' . $val ['FRESHDAY'] . '天';
        $data = array (
            'goodsId' => $val ['GOODSID'], // 商品ID
            'barcodeId' => $val ['BARCODEID'], // 商品条形码
            'title' => $val ['NAME'], // 商品名称
            'abcId' => $val ['ABCID'], // 商品ABC分级
            'unitName' => $val ['UNITNAME'], // 商品最小单位(销售包装)
            'prime_price' => $val ['PRICE'], // 商品价格
            'length' => $val ['LENGTH'], // 长(单位：cm，物流维护)
            'width' => $val ['WIDTH'], // 宽(单位：cm，物流维护)
            'height' => $val ['HEIGHT'], // 高(单位：cm，物流维护)
            'weight' => $val ['WEIGTH'], // 重(单位：cm，物流维护)
            'keepDays' => $val ['KEEPDAY'], // 保质天数(单位：天)
            'freshFlag' => $val ['FRESHFLAG'], // 低温标识：0-常温 1-低温
            'freshDay' => $val ['FRESHDAY'], // 保鲜期天数(单位：天)
            'sync_time' => date (), // 同步时间
            'info' => $goodsSdec, // 商品描述(后台可见)
            'goods_stock' => 0, // 商品库存 默认库存为0，库存自动同步
            'brand' => $val ['BRANDID'], // 品牌ID
            'size' => $val ['SPEC'], // 商品规格
            'pkSpec' => $val ['PKSPEC'] 
        ); // 商品包装规格
           // 'uid' => $shopVal ['uid'],
           // 'status' => 0 ,// 商品状态：0-下架 1-上架
        
        $ids = M ( 'item_base', '', 'DB_CONFIG0' )->field ( 'id' )
            ->where ( "goodsId=%s", array ($val ['GOODSID'] ) )->select ();
        
        if (count ( $ids ) == 0) { // 为空插入数据 需要插入多条
          //Log::write ( 'add', 'DEBUG' );
          $data ['intro'] = ''; // 商品描述
          $data ['img'] = ''; // 商品图片
          $data ['type'] = 1; // 类型：1-商品 2-图片
          $data ['buy_num'] = 0; // 商品卖出数量
          $data ['add_time'] = date (); // 商品添加时间
          $baseid = M ( 'item_base', '', 'DB_CONFIG0' )->add ( $data );
          
          $erpNumArray = $shop->field ( 'uid,erpnum' )->select ();
          foreach ( $erpNumArray as $ek => $ev ) {
            if (! empty ( $ev ['erpnum'] )) {
              $item ['uid'] = $ev ['uid'];
              $item ['goodsId'] = $data ['goodsId'];
              $item ['baseid'] = $baseid;
              $item ['status'] = 0;
              $item ['goods_stock'] = 0;
              $item ['add_time'] = date ();
              //Log::write ( 'sync goods....uid>>' . $ev ['uid'] . '---erpnum---'
                  //. $ev ['erpnum'] . '--goodsid--' . $val ['GOODSID'], 'DEBUG' );
              $ecData = M ( 'item', '', 'DB_CONFIG0' )->add ( $item );
            } else {
              //Log::write ( 'erpnum is empty', 'DEBUG' );
            }
          }
        } else {
          //Log::write ( 'update', 'DEBUG' );
          $ecData = M ( 'item_base', '', 'DB_CONFIG0' )
          ->where ( "goodsId='%s'", array (
              $val ['GOODSID'] 
          ) )->save ( $data ); // 一次更新多条
        }
        // 更新状态
        if ($ecData !== false) {
          unset ( $val ['NUMROW'] );
          M ( 'sdwx_goods_bak', 'DBUSRINWX.', 'DB_CONFIG1' )->add ( $val );
          M ( 'sdwx_goods', 'DBUSRINWX.', 'DB_CONFIG1' )
          ->where ( "SHEETID='%s' and GOODSID='%s'", array (
              $val ['SHEETID'],
              $val ['GOODSID'] 
          ) )->delete ();
        }
      }
    }
  }
  /**
   * 获取品牌信息
   */
  public function getBrandInfo() {
    $result = M ( 'sdwx_brand', null, 'DB_CONFIG1' )->field ( '*' )->where ( "STATUS=%d", array (
        0 
    ) )->order ( 'SHEETID' )->select ();
    if (empty ( $result ))
      return;
    foreach ( $result as $val ) {
      $ecInfo = M ( 'brandlist', '', 'DB_CONFIG0' );
      $id = $ecInfo->field ( 'id' )->where ( "id=%d", array (
          $val ['BRANDID'] 
      ) )->find ();
      $data = array ();
      $data ['id'] = $val ['BRANDID']; // 品牌ID
      $data ['name'] = $val ['BRANDNAME']; // 品牌名称
                                           // $data['ordid'] = $val['BRANDID'];
      if (empty ( $id )) {
        $data ['status'] = 1;
        $ecData = $ecInfo->add ( $data );
      } else {
        $ecData = $ecInfo->where ( 'id=%d', array (
            $val ['BRANDID'] 
        ) )->save ( $data );
      }
      // 更新状态
      if ($ecData !== false) {
        unset ( $val ['NUMROW'] );
        M ( 'sdwx_brand_bak', 'DBUSRINWX.', 'DB_CONFIG1' )->add ( $val );
        M ( 'sdwx_brand', 'DBUSRINWX.', 'DB_CONFIG1' )->where ( 'SHEETID=%s and BRANDID=%s', array (
            $val ['SHEETID'],
            $val ['BRANDID'] 
        ) )->delete ();
      }
    }
  }
  
  /**
   * 更新库存信息
   *
   * @param string $shopId          
   */
  public function updateInventory() {
    /*
     * dump(8 % 100);
     * dump(8 % 100 != 100);
     * exit();
     */
    $base = 100;
    $result = M ( 'shop' );
    $erpNumArray = $result->where ( 'erpnum != ' . "''" . ' and erpnum is not null ' )->field ( 'uid,erpnum' )->select ();
    foreach ( $erpNumArray as $shopVal ) {
      $length = 100;
      if (! empty ( $shopVal ['erpnum'] )) {
        while ( $length != 0 ) {
          $inventory = M ( 'sdwx_inventory', null, 'DB_CONFIG1' )->field ( '*' )->where ( "SHOPID='%s' and STATUS=%d", array (
              $shopVal ['erpnum'],
              0 
          ) )->limit ( $base )->select ();
          $length = count ( $inventory );
          /* //Log::write ( 'length>>' . $length, 'DEBUG' ); */
          if (empty ( $inventory )) {
            continue;
          }
          foreach ( $inventory as $val ) {
            /*
             * //Log::write ( 'update inventory,goodsid--' . $val ['GOODSID']
             * . '---' . $val ['QTY'] );
             */
            $where = array (
                'goodsId' => $val ['GOODSID'],
                'uid' => $shopVal ['uid'] 
            );
            // 根据uidgoodsid 查询是不是有这个店铺对应的商品，没有则进行新增
            $tmp = M ( 'item', '', 'DB_CONFIG0' )->where ( $where )->find ();
            if ($tmp) { // 更新
              $ecData = M ( 'item', '', 'DB_CONFIG0' )->where ( $where )->save ( array (
                  'goods_stock' => $val ['QTY'],
                  'sync_time' => date () 
              ) );
            } else { // 新增
              $baseid = M ( 'item_base', '', 'DB_CONFIG0' )->where ( 'goodsid = ' . $val ['GOODSID'] )->find ();
              if ($baseid) {
                $item ['uid'] = $shopVal ['uid'];
                $item ['goodsId'] = $val ['GOODSID'];
                $item ['baseid'] = $baseid ['id'];
                $item ['status'] = 0;
                $item ['goods_stock'] = $val ['QTY'];
                $item ['add_time'] = date ();
                $item ['sync_time'] = date ();
                /*
                 * //Log::write ( 'sync add goods....uid>>' . $shopVal ['uid']
                 * . '--goodsid--' . $val ['GOODSID'], 'DEBUG' );
                 */
                $ecData = M ( 'item', '', 'DB_CONFIG0' )->add ( $item );
              }
            }
            
            /* //Log::write ( 'begin zima.......' ); */
            // 子码 begin
            // 查询母码对应的子码
            $changeunitgoods = M ( 'sdwx_changeunitgoods', null, 'DB_CONFIG1' )->field ( 'ZGOODSID,QTY' )->where ( "mgoodsid='%s'", array (
                $val ['GOODSID'] 
            ) )->select ();
            foreach ( $changeunitgoods as $zival ) {
              /*
               * //Log::write ( 'zima inventory,ZGOODSID--' . $zival ['ZGOODSID']
               * . '--QTY-' . $zival ['QTY'] );
               */
              $map = array (
                  'goodsId' => $zival ['ZGOODSID'],
                  'uid' => $shopVal ['uid'] 
              );
              $goods_stock = $val ['QTY'] / $zival ['QTY'];
              // 根据uid goodsid 查询是不是有这个店铺对应的商品，没有则进行新增
              $tmp = M ( 'item', '', 'DB_CONFIG0' )->where ( $map )->find ();
              if ($tmp) {
                $ecData = M ( 'item', '', 'DB_CONFIG0' )->where ( $map )->save ( array (
                    'goods_stock' => $goods_stock,
                    'sync_time' => date () 
                ) );
              } else {
                $baseid = M ( 'item_base', '', 'DB_CONFIG0' )->where ( 'goodsid = ' . $zival ['ZGOODSID'] )->find ();
                if ($baseid) {
                  $item ['uid'] = $shopVal ['uid'];
                  $item ['goodsId'] = $zival ['ZGOODSID'];
                  $item ['baseid'] = $baseid ['id'];
                  $item ['status'] = 0;
                  $item ['goods_stock'] = $goods_stock;
                  $item ['add_time'] = date ();
                  /* //Log::write ( 'sync zima  add goods....uid>>' 
                      . $shopVal ['uid'] . '--goodsid--' 
                      . $zival ['ZGOODSID'], 'DEBUG' ); */
                  $ecData = M ( 'item', '', 'DB_CONFIG0' )->add ( $item );
                }
              }
            }
            // 子码 end
            /* //Log::write ( 'ned zima.......' ); */
            
            // $ecData = M ( 'item', '', 'DB_CONFIG0' )->where ( $where )
            //-> save ( array ('goods_stock' => $val ['QTY'] ) );
            // if ($ecData !== false){
            unset ( $val ['NUMROW'] );
            M ( 'sdwx_inventory_bak', 'DBUSRINWX.', 'DB_CONFIG1' )->add ( $val );
            M ( 'sdwx_inventory', 'DBUSRINWX.', 'DB_CONFIG1' )
              ->where ( "SHEETID=%s and SHOPID='%s' and GOODSID=%s", array (
                $val ['SHEETID'],
                $shopVal ['erpnum'],
                $val ['GOODSID'] 
            ) )->delete ();
            // }
          }
        }
      }
    }
  }
  
  /**
   * 更新商品条形码信息
   */
  public function updateBarcode() {
    $base = 100;
    $length = 100;
    while ( $length % $base != $base ) {
      $result = M ( 'sdwx_barcode', null, 'DB_CONFIG1' )->field ( '*' )
      ->where ( "STATUS=%d", array (
          0 
      ) )->select ();
      $length = count ( $result );
      if (empty ( $result ))
        return;
      foreach ( $result as $val ) {
        $ecData = M ( 'item', '', 'DB_CONFIG0' )->where ( 'goodsId=%s', array (
            $val ['GOODSID'] 
        ) )->save ( array (
            'barcodeId' => $val ['BARCODEID'] 
        ) );
        if ($ecData !== false) {
          unset ( $val ['NUMROW'] );
          M ( 'sdwx_barcode_bak', 'DBUSRINWX.', 'DB_CONFIG1' )->add ( $val );
          M ( 'sdwx_barcode', 'DBUSRINWX.', 'DB_CONFIG1' )
          ->where ( "SHEETID=%s and GOODSID=%s", array (
              $val ['SHEETID'],
              $val ['GOODSID'] 
          ) )->delete ();
        }
      }
    }
  }
}