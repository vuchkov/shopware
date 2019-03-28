<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Bundle\ESIndexingBundle\Product;

use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Bundle\ESIndexingBundle\FieldMappingInterface;
use Shopware\Bundle\ESIndexingBundle\IdentifierSelector;
use Shopware\Bundle\ESIndexingBundle\MappingInterface;
use Shopware\Bundle\ESIndexingBundle\TextMappingInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\Shop;

class ProductMapping implements MappingInterface
{
    const TYPE = 'product';

    /**
     * @var IdentifierSelector
     */
    private $identifierSelector;

    /**
     * @var FieldMappingInterface
     */
    private $fieldMapping;

    /**
     * @var TextMappingInterface
     */
    private $textMapping;

    /**
     * @var CrudService
     */
    private $crudService;

    /**
     * @var bool
     */
    private $isDynamic;

    /**
     * @param bool $isDynamic
     */
    public function __construct(
        IdentifierSelector $identifierSelector,
        FieldMappingInterface $fieldMapping,
        TextMappingInterface $textMapping,
        CrudService $crudService,
        $isDynamic = true
    ) {
        $this->identifierSelector = $identifierSelector;
        $this->fieldMapping = $fieldMapping;
        $this->textMapping = $textMapping;
        $this->crudService = $crudService;
        $this->isDynamic = $isDynamic;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function get(Shop $shop)
    {
        return [
            'dynamic' => $this->isDynamic,
            '_source' => [
                'includes' => ['id', 'mainVariantId', 'variantId', 'number'],
            ],
            'properties' => [
                // Identifiers
                'id' => ['type' => 'long'],
                'mainVariantId' => ['type' => 'long'],
                'variantId' => ['type' => 'long'],

                // Number fields
                'number' => array_merge($this->textMapping->getTextField(), ['analyzer' => 'standard']),
                'ean' => $this->textMapping->getKeywordField(),
                'manufacturerNumber' => $this->fieldMapping->getLanguageField($shop),

                // Language fields
                'name' => $this->fieldMapping->getLanguageField($shop),
                'shortDescription' => $this->fieldMapping->getLanguageField($shop),
                'longDescription' => $this->fieldMapping->getLanguageField($shop),
                'additional' => $this->fieldMapping->getLanguageField($shop),
                'keywords' => $this->fieldMapping->getLanguageField($shop),
                'metaTitle' => $this->fieldMapping->getLanguageField($shop),

                // Other fields
                'calculatedPrices' => $this->getCalculatedPricesMapping($shop),
                'minStock' => ['type' => 'long'],
                'stock' => ['type' => 'long'],
                'sales' => ['type' => 'long'],
                'states' => $this->textMapping->getKeywordField(),
                'template' => $this->textMapping->getKeywordField(),
                'shippingTime' => $this->textMapping->getKeywordField(),
                'weight' => ['type' => 'double'],
                'height' => ['type' => 'long'],
                'length' => ['type' => 'long'],
                'width' => ['type' => 'double'],

                // Grouped id fields
                'blockedCustomerGroupIds' => ['type' => 'long'],
                'categoryIds' => ['type' => 'long'],

                // Flags
                'isMainVariant' => ['type' => 'boolean'],
                'closeouts' => ['type' => 'boolean'],
                'allowsNotification' => ['type' => 'boolean'],
                'hasProperties' => ['type' => 'boolean'],
                'hasAvailableVariant' => ['type' => 'boolean'],
                'hasConfigurator' => ['type' => 'boolean'],
                'hasEsd' => ['type' => 'boolean'],
                'isPriceGroupActive' => ['type' => 'boolean'],
                'shippingFree' => ['type' => 'boolean'],
                'highlight' => ['type' => 'boolean'],
                'customerPriceCount' => ['type' => 'long'],
                'fallbackPriceCount' => ['type' => 'long'],

                // Dates
                'formattedCreatedAt' => ['type' => 'date', 'format' => 'yyyy-MM-dd'],
                'formattedUpdatedAt' => ['type' => 'date', 'format' => 'yyyy-MM-dd'],
                'formattedReleaseDate' => ['type' => 'date', 'format' => 'yyyy-MM-dd'],

                // Nested structs
                'manufacturer' => $this->getManufacturerMapping($shop),
                'priceGroup' => $this->getPriceGroupMapping(),
                'properties' => $this->getPropertyMapping($shop),
                'esd' => $this->getEsdMapping(),
                'tax' => $this->getTaxMapping(),
                'unit' => $this->getUnitMapping(),

                'attributes' => $this->getAttributeMapping(),
                'configuration' => $this->getVariantOptionsMapping($shop),

                'voteAverage' => $this->getVoteAverageMapping(),
            ],
        ];
    }

    /**
     * @return array
     */
    private function getPropertyMapping(Shop $shop)
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'long'],
                'name' => $this->fieldMapping->getLanguageField($shop),
                'position' => ['type' => 'long'],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getUnitMapping()
    {
        return [
            'properties' => [
                'id' => ['type' => 'long'],
                'name' => $this->textMapping->getKeywordField(),
                'unit' => $this->textMapping->getKeywordField(),
                'minPurchase' => ['type' => 'long'],
                'maxPurchase' => ['type' => 'long'],
                'packUnit' => $this->textMapping->getKeywordField(),
                'purchaseStep' => ['type' => 'long'],
                'purchaseUnit' => ['type' => 'long'],
                'referenceUnit' => ['type' => 'long'],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getManufacturerMapping(Shop $shop)
    {
        return [
            'properties' => [
                'id' => ['type' => 'long'],
                'name' => $this->fieldMapping->getLanguageField($shop),
                'description' => $this->textMapping->getKeywordField(),
                'coverFile' => $this->textMapping->getKeywordField(),
                'link' => $this->textMapping->getKeywordField(),
                'metaTitle' => $this->textMapping->getKeywordField(),
                'metaDescription' => $this->textMapping->getKeywordField(),
                'metaKeywords' => $this->textMapping->getKeywordField(),
            ],
        ];
    }

    /**
     * @return array
     */
    private function getPriceGroupMapping()
    {
        return [
            'properties' => [
                'id' => ['type' => 'long'],
                'name' => $this->textMapping->getKeywordField(),
            ],
        ];
    }

    /**
     * @return array
     */
    private function getEsdMapping()
    {
        return [
            'properties' => [
                'id' => ['type' => 'long'],
                'file' => $this->textMapping->getKeywordField(),
                'hasSerials' => ['type' => 'boolean'],
                'createdAt' => [
                    'properties' => [
                        'date' => $this->textMapping->getKeywordField(),
                        'timezone' => $this->textMapping->getKeywordField(),
                        'timezone_type' => ['type' => 'long'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getTaxMapping()
    {
        return [
            'properties' => [
                'id' => ['type' => 'long'],
                'name' => $this->textMapping->getKeywordField(),
                'tax' => ['type' => 'long'],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getCalculatedPricesMapping(Shop $shop)
    {
        $prices = [];
        $customerGroups = $this->identifierSelector->getCustomerGroupKeys();
        $currencies = $this->identifierSelector->getShopCurrencyIds($shop->getId());
        if (!$shop->isMain()) {
            $currencies = $this->identifierSelector->getShopCurrencyIds($shop->getParentId());
        }

        foreach ($currencies as $currency) {
            foreach ($customerGroups as $customerGroup) {
                $key = $customerGroup . '_' . $currency;
                $prices[$key] = $this->getPriceMapping();
            }
        }

        return ['properties' => $prices];
    }

    /**
     * @return array
     */
    private function getPriceMapping()
    {
        return [
            'properties' => [
                'calculatedPrice' => ['type' => 'double'],
                'calculatedReferencePrice' => ['type' => 'double'],
                'calculatedPseudoPrice' => ['type' => 'double'],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getAttributeMapping()
    {
        $attributes = $this->crudService->getList('s_articles_attributes');

        $properties = [];
        foreach ($attributes as $attribute) {
            $name = $attribute->getColumnName();
            $type = $attribute->getElasticSearchType();

            if ($attribute->isIdentifier()) {
                continue;
            }

            switch ($type['type']) {
                case 'keyword':
                    $type = $this->textMapping->getKeywordField();
                    $type['fields']['raw'] = $this->textMapping->getKeywordField();
                    break;

                case 'string':
                case 'text':
                    $type = $this->textMapping->getTextField();
                    $type['fields']['raw'] = $this->textMapping->getKeywordField();
                    break;
            }

            $properties[$name] = $type;
        }

        return [
            'properties' => [
                'core' => [
                    'properties' => $properties,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getVariantOptionsMapping(Shop $shop)
    {
        return [
            'properties' => [
                'id' => ['type' => 'long'],
                'name' => $this->fieldMapping->getLanguageField($shop),
                'description' => $this->fieldMapping->getLanguageField($shop),
                'options' => [
                    'properties' => [
                        'id' => ['type' => 'long'],
                        'name' => $this->fieldMapping->getLanguageField($shop),
                        'description' => $this->fieldMapping->getLanguageField($shop),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getVoteAverageMapping()
    {
        return [
            'properties' => [
                'average' => [
                    'type' => 'double',
                ],
            ],
        ];
    }
}
