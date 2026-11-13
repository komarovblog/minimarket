<?php
/** Static wiring: Joomla accounts, live email and only installed plugin methods. */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$admin=$root.'admin/';
$site=$root.'site/';
foreach([
 'admin/src/Service/NotificationPolicy.php',
 'admin/src/View/Notificationmethods/HtmlView.php',
 'admin/src/Controller/NotificationmethodsController.php',
 'admin/tmpl/notificationmethods/default.php',
 'site/src/Service/NotificationPreferences.php',
 'site/src/Service/NotificationSender.php',
 'site/src/Controller/NotificationpreferencesController.php',
 'site/src/View/Notificationpreferences/HtmlView.php',
 'site/tmpl/notificationpreferences/default.php'
] as $p)if(!is_file($root.$p))throw new RuntimeException('MM610_NOTIFY_FILE_'.$p);
$policy=file_get_contents($admin.'src/Service/NotificationPolicy.php');
$sender=file_get_contents($site.'src/Service/NotificationSender.php');
$preferences=file_get_contents($site.'src/Service/NotificationPreferences.php');
$editor=file_get_contents($admin.'src/Controller/NotificationmethodsController.php');
foreach(['order_created','pickup_ready','pickup_reminder','pickup_final',
          'order_cancelled','delivery_updated'] as $event)
 if(!str_contains($policy,$event))throw new RuntimeException('MM610_NOTIFY_EVENT_'.$event);
if(!str_contains($preferences,"'notification\\_%'")
  ||!str_contains($preferences,'enabled=1'))
 throw new RuntimeException('MM610_NOTIFY_INSTALLED_ONLY');
if(!str_contains($sender,"notification_email_enabled")
  ||!str_contains($sender,"notification_preferences")
  ||!str_contains($sender,"#__users")
  ||!str_contains($sender,'notification_log')
  ||!str_contains($sender,'getAffectedRows()'))
 throw new RuntimeException('MM610_NOTIFY_SENDER_GUARDS');
if(!str_contains($editor,"Session::checkToken")
 ||!str_contains($editor,"core.options"))
 throw new RuntimeException('MM610_NOTIFY_ADMIN_ACL_CSRF');
$siteController=file_get_contents($site.'src/Controller/NotificationpreferencesController.php');
if(!str_contains($siteController,'Session::checkToken')
 ||!str_contains($siteController,'getIdentity()->id')
 && !str_contains($siteController,'$user->id'))
 throw new RuntimeException('MM610_NOTIFY_ACCOUNT_OWNERSHIP');
foreach(['ru-RU','en-GB'] as $loc){
 $adm=parse_ini_file($admin.'language/'.$loc.'/com_minimarket.ini');
 $pub=parse_ini_file($site.'language/'.$loc.'/com_minimarket.ini');
 foreach(['COM_MINIMARKET_NOTIFY_EVENT_PICKUP_READY','COM_MINIMARKET_NOTIFY_EMAIL'] as $key)
  if(!isset($adm[$key])||!isset($pub[$key]))throw new RuntimeException('MM610_NOTIFY_I18N_'.$key);
}
echo "MM610_NOTIFICATIONS_WIRING_OK\n";
