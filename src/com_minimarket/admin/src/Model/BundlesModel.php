<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;
\defined('_JEXEC') or die;

/** Separate administrator list with the same stock, price, SEO and category model. */
final class BundlesModel extends ProductsModel
{
    protected string $entityType='bundle';
}
