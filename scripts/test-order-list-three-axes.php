<?php
/** Three independent status columns and filters; no artificial reserve deadline. */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/com_minimarket/admin/';
$view=file_get_contents($root.'src/View/Orders/HtmlView.php');
$tpl=file_get_contents($root.'tmpl/orders/default.php');
foreach(['payment_status','fulfillment_status','status','filter_payment','filter_fulfillment'] as $key){
 if(!str_contains($view,$key))throw new RuntimeException('MM_LIST_3AXES_VIEW: '.$key);
 if(!str_contains($tpl,$key))throw new RuntimeException('MM_LIST_3AXES_TPL: '.$key);
}
if(!str_contains($tpl,"'cancelled'=>'secondary'")||
   (!str_contains($tpl,'COM_MINIMARKET_ORDER_STATUS_')||!str_contains($tpl,'$order->status')))
 throw new RuntimeException('MM_LIST_CANCELLED_NOT_VISIBLE');
if(str_contains($tpl,'reserved_until')||str_contains($tpl,'reservation_expires_at'))
 throw new RuntimeException('MM_LIST_FAKE_RESERVE');
if(substr_count($tpl,'data-label="<?php echo Text::')<6)
 throw new RuntimeException('MM_LIST_NATIVE_COLUMNS_MISSING');
echo "MM_ORDER_LIST_THREE_AXES_OK\n";
