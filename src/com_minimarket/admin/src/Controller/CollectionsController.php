<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\AdminController;
final class CollectionsController extends AdminController
{
    protected $text_prefix='COM_MINIMARKET_BULK';
    public function getModel($name='Collection',$prefix='Administrator',$config=['ignore_request'=>true])
    { return parent::getModel($name,$prefix,$config); }
}
