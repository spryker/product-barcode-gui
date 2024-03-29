<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBarcodeGui\Communication\Table;

use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Orm\Zed\Product\Persistence\Map\SpyProductLocalizedAttributesTableMap;
use Orm\Zed\Product\Persistence\SpyProduct;
use Orm\Zed\Product\Persistence\SpyProductQuery;
use Spryker\Zed\Gui\Communication\Table\AbstractTable;
use Spryker\Zed\Gui\Communication\Table\TableConfiguration;
use Spryker\Zed\ProductBarcodeGui\Dependency\Facade\ProductBarcodeGuiToLocaleFacadeInterface;
use Spryker\Zed\ProductBarcodeGui\Dependency\Facade\ProductBarcodeGuiToProductBarcodeFacadeInterface;

/**
 * @uses SpyProduct
 * @uses SpyProductQuery
 * @uses SpyProductLocalizedAttributesTableMap
 */
class ProductBarcodeTable extends AbstractTable
{
    /**
     * @var string
     */
    protected const COL_ID_PRODUCT = 'id_product';

    /**
     * @var string
     */
    protected const COL_PRODUCT_SKU = 'sku';

    /**
     * @var string
     */
    protected const COL_PRODUCT_NAME = 'name';

    /**
     * @var string
     */
    protected const COL_BARCODE = 'barcode';

    /**
     * @var string
     */
    protected const BARCODE_IMAGE_TEMPLATE = '<img src="%s,%s">';

    /**
     * @var \Spryker\Zed\ProductBarcodeGui\Dependency\Facade\ProductBarcodeGuiToProductBarcodeFacadeInterface
     */
    protected $productBarcodeFacade;

    /**
     * @var \Spryker\Zed\ProductBarcodeGui\Dependency\Facade\ProductBarcodeGuiToLocaleFacadeInterface
     */
    protected $localeFacade;

    /**
     * @param \Spryker\Zed\ProductBarcodeGui\Dependency\Facade\ProductBarcodeGuiToProductBarcodeFacadeInterface $productBarcodeFacade
     * @param \Spryker\Zed\ProductBarcodeGui\Dependency\Facade\ProductBarcodeGuiToLocaleFacadeInterface $localeFacade
     */
    public function __construct(
        ProductBarcodeGuiToProductBarcodeFacadeInterface $productBarcodeFacade,
        ProductBarcodeGuiToLocaleFacadeInterface $localeFacade
    ) {
        $this->productBarcodeFacade = $productBarcodeFacade;
        $this->localeFacade = $localeFacade;
    }

    /**
     * @param \Spryker\Zed\Gui\Communication\Table\TableConfiguration $config
     *
     * @return \Spryker\Zed\Gui\Communication\Table\TableConfiguration
     */
    protected function configure(TableConfiguration $config): TableConfiguration
    {
        $config->setHeader([
            static::COL_ID_PRODUCT => 'Product ID',
            static::COL_PRODUCT_NAME => 'Product Name',
            static::COL_PRODUCT_SKU => 'SKU',
            static::COL_BARCODE => 'Barcode',
        ]);

        $config->setSearchable([
            static::COL_ID_PRODUCT,
            static::COL_PRODUCT_NAME,
            static::COL_PRODUCT_SKU,
        ]);

        $config->setSortable([
            static::COL_ID_PRODUCT,
            static::COL_PRODUCT_NAME,
            static::COL_PRODUCT_SKU,
        ]);

        $config->setRawColumns([
            static::COL_BARCODE,
        ]);

        return $config;
    }

    /**
     * @param \Generated\Shared\Transfer\LocaleTransfer $localeTransfer
     *
     * @return \Orm\Zed\Product\Persistence\SpyProductQuery
     */
    protected function prepareTableQuery(LocaleTransfer $localeTransfer): SpyProductQuery
    {
        $localeTransfer->requireIdLocale();

        /** @var \Orm\Zed\Product\Persistence\SpyProductQuery $query */
        $query = SpyProductQuery::create()
            ->innerJoinSpyProductLocalizedAttributes()
            ->useSpyProductLocalizedAttributesQuery()
            ->filterByFkLocale($localeTransfer->getIdLocale())
            ->endUse()
            ->withColumn(SpyProductLocalizedAttributesTableMap::COL_NAME, static::COL_PRODUCT_NAME);

        return $query;
    }

    /**
     * @param \Spryker\Zed\Gui\Communication\Table\TableConfiguration $config
     *
     * @return array
     */
    protected function prepareData(TableConfiguration $config): array
    {
        $queryResults = $this->runQuery(
            $this->prepareQuery(),
            $config,
            true,
        );

        $results = [];

        foreach ($queryResults as $queryItem) {
            $results[] = $this->generateItem($queryItem);
        }

        return $results;
    }

    /**
     * @return \Orm\Zed\Product\Persistence\SpyProductQuery
     */
    protected function prepareQuery(): SpyProductQuery
    {
        $localeTransfer = $this->localeFacade->getCurrentLocale();

        return $this->prepareTableQuery($localeTransfer);
    }

    /**
     * @param \Orm\Zed\Product\Persistence\SpyProduct $product
     *
     * @return array
     */
    protected function generateItem(SpyProduct $product): array
    {
        $sku = $product->getSku();
        $productName = $product->getVirtualColumn(static::COL_PRODUCT_NAME);

        return [
            static::COL_ID_PRODUCT => $this->formatInt($product->getIdProduct()),
            static::COL_PRODUCT_SKU => $sku,
            static::COL_PRODUCT_NAME => $productName,
            static::COL_BARCODE => $this->getBarcodeImageBySku($sku),
        ];
    }

    /**
     * @param string $sku
     *
     * @return string
     */
    protected function getBarcodeImageBySku(string $sku): string
    {
        $productTransfer = new ProductConcreteTransfer();
        $productTransfer->setSku($sku);
        $barcodeResponseTransfer = $this->productBarcodeFacade->generateBarcode($productTransfer);

        return sprintf(
            static::BARCODE_IMAGE_TEMPLATE,
            $barcodeResponseTransfer->getEncoding(),
            $barcodeResponseTransfer->getCode(),
        );
    }
}
