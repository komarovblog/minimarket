<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\AdminController;
final class BrandsController extends AdminController
{
    protected $text_prefix='COM_MINIMARKET_BULK';
    public function getModel($name='Brand',$prefix='Administrator',$config=['ignore_request'=>true])
    { return parent::getModel($name,$prefix,$config); }
}
