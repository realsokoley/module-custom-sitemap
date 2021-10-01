<?php

/**
 * @author Sokoley
 * @copyright 2021 Sokoley
 * @package Sokoley_CustomSitemap
 */
namespace Sokoley\CustomSitemap\Model;

use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\SitemapConfigReaderInterface;
use Magento\Sitemap\Model\SitemapItemInterface;
use Magento\Sitemap\Model\ItemProvider\Product;
use Magento\Sitemap\Model\ItemProvider\Category;
use Magento\Sitemap\Model\ItemProvider\CmsPage;

/**
 * Class Sitemap
 * @package Sokoley\CustomSitemap\Model
 * @method int getStoreId()
 */
class Sitemap extends \Magento\Sitemap\Model\Sitemap
{
    const PRODUCTS_TITLE = 'products';
    const CATEGORIES_TITLE = 'categories';
    const CMS_PAGES_TITLE = 'cms_pages';

    /**
     * @var \Sokoley\CustomSiteMap\Model\Config
     */
    private $config;

    /**
     * @var array
     */
    private $customSiteMaps = [];

    /**
     * @var CmsPage
     */
    private $cmsPageProvider;

    /**
     * @var Category
     */
    private $categoryProvider;

    /**
     * @var Product
     */
    private $productProvider;


    public function __construct(
        Config $config,
        Product $productProvider,
        Category $categoryProvider,
        CmsPage $cmsPageProvider,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Escaper $escaper,
        \Magento\Sitemap\Helper\Data $sitemapData,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Sitemap\Model\ResourceModel\Catalog\CategoryFactory $categoryFactory,
        \Magento\Sitemap\Model\ResourceModel\Catalog\ProductFactory $productFactory,
        \Magento\Sitemap\Model\ResourceModel\Cms\PageFactory $cmsFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $modelDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        \Magento\Config\Model\Config\Reader\Source\Deployed\DocumentRoot $documentRoot = null,
        ItemProviderInterface $itemProvider = null,
        SitemapConfigReaderInterface $configReader = null,
        \Magento\Sitemap\Model\SitemapItemInterfaceFactory $sitemapItemFactory = null
    )
    {
        $this->config = $config;
        $this->productProvider = $productProvider;
        $this->categoryProvider = $categoryProvider;
        $this->cmsPageProvider = $cmsPageProvider;
        parent::__construct($context, $registry, $escaper, $sitemapData, $filesystem, $categoryFactory, $productFactory, $cmsFactory, $modelDate, $storeManager, $request, $dateTime, $resource, $resourceCollection, $data, $documentRoot, $itemProvider, $configReader, $sitemapItemFactory);
    }


    /**
     * Generate XML file
     *
     * @see http://www.sitemaps.org/protocol.html
     *
     * @return \Magento\Sitemap\Model\Sitemap
     */
    public function generateXml()
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore($this->getStoreId());
        if ($this->config->isActive($store)) {
            $this->initCustomSiteMapsMapper($store);
            foreach ($this->customSiteMaps as $siteMapTitle => $siteMapFilename) {
                $this->initCustomSitemapItems($siteMapTitle);
                $this->generateCustomSitemap($siteMapFilename);
            }
            $this->initCustomSitemapItems();
            $this->_createSitemap($this->getSitemapFilename(), self::TYPE_INDEX);
            foreach ($this->customSiteMaps as $siteMapFilename) {
                $xml = $this->_getSitemapIndexRow($siteMapFilename, $this->_getCurrentDateTime());
                $this->_writeSitemapRow($xml);
            }
            $this->_finalizeSitemap(self::TYPE_INDEX);
            $this->setSitemapTime($this->_dateModel->gmtDate('Y-m-d H:i:s'));
            $this->save();
            return $this;
        }

        return parent::generateXml();
    }

    /**
     * @param $fileName
     * @return $this
     * @throws \Exception
     */
    private function generateCustomSitemap($fileName)
    {
        /** @var $item SitemapItemInterface */
        foreach ($this->_sitemapItems as $item) {
            $xml = $this->_getSitemapRow(
                $item->getUrl(),
                $item->getUpdatedAt(),
                $item->getChangeFrequency(),
                $item->getPriority(),
                $item->getImages()
            );

            if (!$this->_fileSize) {
                $this->_createSitemap($fileName);
            }

            $this->_writeSitemapRow($xml);
            $this->_lineCount++;
            $this->_fileSize += strlen($xml);
        }

        $this->_finalizeSitemap();
        $this->setSitemapTime($this->_dateModel->gmtDate('Y-m-d H:i:s'));
        $this->save();

        return $this;
    }

    /**
     * Initialize sitemap
     *
     * @return void
     */
    private function initCustomSitemapItems($siteMapTitle = null)
    {
        switch ($siteMapTitle) {
            case self::PRODUCTS_TITLE:
                $this->_sitemapItems = $this->productProvider->getItems($this->getStoreId());
                break;
            case self::CATEGORIES_TITLE:
                $this->_sitemapItems = $this->categoryProvider->getItems($this->getStoreId());
                break;
            case self::CMS_PAGES_TITLE:
                $this->_sitemapItems = $this->cmsPageProvider->getItems($this->getStoreId());
                break;
            default:
                break;
        }

        $this->_tags = [
            self::TYPE_INDEX => [
                self::OPEN_TAG_KEY => '<?xml version="1.0" encoding="UTF-8"?>' .
                    PHP_EOL .
                    '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' .
                    PHP_EOL,
                self::CLOSE_TAG_KEY => '</sitemapindex>',
            ],
            self::TYPE_URL => [
                self::OPEN_TAG_KEY => '<?xml version="1.0" encoding="UTF-8"?>' .
                    PHP_EOL .
                    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' .
                    ' xmlns:content="http://www.google.com/schemas/sitemap-content/1.0"' .
                    ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' .
                    PHP_EOL,
                self::CLOSE_TAG_KEY => '</urlset>',
            ],
        ];
    }

    /**
     * @param \Magento\Store\Model\Store $store
     * @return array
     */
    private function initCustomSiteMapsMapper($store)
    {
        if (!empty($this->customSiteMaps)) {
            return $this->customSiteMaps;
        }

        $this->customSiteMaps[self::PRODUCTS_TITLE] = $this->config->getProductsSitemap($store);
        $this->customSiteMaps[self::CATEGORIES_TITLE] = $this->config->getCategoriesSitemap($store);
        $this->customSiteMaps[self::CMS_PAGES_TITLE] = $this->config->getCmsPagesSitemap($store);

        return $this->customSiteMaps;
    }
}
