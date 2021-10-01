<?php

/**
 * @author Sokoley
 * @copyright 2021 Sokoley
 * @package Sokoley_CustomSitemap
 */
namespace Sokoley\CustomSitemap\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const IS_ACTIVE = 'sokoley_custom_sitemap/general/is_active';
    const PRODUCTS_SITEMAP = 'sokoley_custom_sitemap/general/products_sitemap';
    const CATEGORIES_SITEMAP = 'sokoley_custom_sitemap/general/categories_sitemap';
    const CMS_PAGES_SITEMAP = 'sokoley_custom_sitemap/general/cms_pages_sitemap';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isActive($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::IS_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getProductsSitemap($store = null)
    {
        return $this->scopeConfig->getValue(
            self::PRODUCTS_SITEMAP,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getCategoriesSitemap($store = null)
    {
        return $this->scopeConfig->getValue(
            self::CATEGORIES_SITEMAP,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getCmsPagesSitemap($store = null)
    {
        return $this->scopeConfig->getValue(
            self::CMS_PAGES_SITEMAP,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
