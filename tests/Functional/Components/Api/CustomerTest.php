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

namespace Shopware\Tests\Functional\Components\Api;

use Shopware\Components\Api\Resource\Address;
use Shopware\Components\Api\Resource\Customer;
use Shopware\Components\Api\Resource\Resource;
use Shopware\Components\Random;
use Shopware\Models\Attribute\Customer as CustomerAttribute;

class CustomerTest extends TestCase
{
    /**
     * @var Customer
     */
    protected $resource;

    protected function setUp()
    {
        parent::setUp();
        Shopware()->Container()->get('dbal_connection')->exec('UPDATE s_core_countries SET allow_shipping = 0 WHERE id = 25');
    }

    /**
     * @return Customer
     */
    public function createResource()
    {
        return new Customer();
    }

    /**
     * @expectedException \Shopware\Components\Api\Exception\ValidationException
     */
    public function testCreateWithNonUniqueEmailShouldThrowException()
    {
        $testData = [
            'password' => 'fooobar',
            'active' => true,
            'email' => 'test@example.com',

            'billing' => [
                'salutation' => 'mr',
                'zipcode' => '12345',
                'city' => 'Musterhausen',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'street' => 'Musterstr. 123',
                'additionalAddressLine1' => 'Address Billing Addition 1',
                'additionalAddressLine2' => 'Address Billing Addition 2',
                'country' => '2',
                'attribute' => [
                    'text1' => 'Freitext1',
                    'text2' => 'Freitext2',
                ],
            ],

            'shipping' => [
                'salutation' => 'Mr',
                'company' => 'Widgets Inc.',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'additionalAddressLine1' => 'Address Shipping Addition 1',
                'additionalAddressLine2' => 'Address Shipping Addition 2',
                'country' => '2',
                'street' => 'Musterstr. 123',
                'zipcode' => '12345',
                'city' => 'Mustercity',
                'attribute' => [
                    'text1' => 'Freitext1',
                    'text2' => 'Freitext2',
                ],
            ],
        ];

        $this->resource->create($testData);
    }

    public function testCreateShouldBeSuccessful()
    {
        $date = new \DateTime();
        $date->modify('-10 days');
        $firstlogin = $date->format(\DateTime::ISO8601);

        $date->modify('+2 day');
        $lastlogin = $date->format(\DateTime::ISO8601);

        $birthday = \DateTime::createFromFormat('Y-m-d', '1986-12-20')->format(\DateTime::ISO8601);

        $testData = [
            'password' => 'fooobar',
            'email' => Random::getAlphanumericString(5) . 'test@foobar.com',
            'number' => 'testnumber' . uniqid(),
            'firstlogin' => $firstlogin,
            'lastlogin' => $lastlogin,

            'salutation' => 'mr',
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'birthday' => $birthday,

            'billing' => [
                'salutation' => 'mr',
                'zipcode' => '12345',
                'city' => 'Musterhausen',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'street' => 'Musterstr. 123',
                'additionalAddressLine1' => 'Address Billing Addition 1',
                'additionalAddressLine2' => 'Address Billing Addition 2',
                'country' => '2',
                'attribute' => [
                    'text1' => 'Freitext1',
                    'text2' => 'Freitext2',
                ],
            ],

            'shipping' => [
                'salutation' => 'Mr',
                'company' => 'Widgets Inc.',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'additionalAddressLine1' => 'Address Shipping Addition 1',
                'additionalAddressLine2' => 'Address Shipping Addition 2',
                'country' => '2',
                'street' => 'Musterstr. 123',
                'zipcode' => '12345',
                'city' => 'Mustercity',
                'attribute' => [
                    'text1' => 'Freitext1',
                    'text2' => 'Freitext2',
                ],
            ],

            'debit' => [
                'account' => 'Fake Account',
                'bankCode' => '55555555',
                'bankName' => 'Fake Bank',
                'accountHolder' => 'Max Mustermann',
            ],
        ];

        /** @var \Shopware\Models\Customer\Customer $customer */
        $customer = $this->resource->create($testData);

        $this->assertInstanceOf('\Shopware\Models\Customer\Customer', $customer);
        $this->assertGreaterThan(0, $customer->getId());

        // Test default values
        $this->assertEquals($customer->getShop()->getId(), 1);
        $this->assertEquals($customer->getAccountMode(), 0);
        $this->assertEquals($customer->getGroup()->getKey(), 'EK');
        $this->assertEquals($customer->getActive(), true);

        $this->assertEquals($customer->getEmail(), $testData['email']);

        $this->assertEquals($customer->getDefaultBillingAddress()->getFirstName(), $testData['billing']['firstname']);
        $this->assertEquals($customer->getDefaultBillingAddress()->getFirstname(), $testData['billing']['firstname']);

        $this->assertEquals($customer->getDefaultBillingAddress()->getAttribute()->getText1(), $testData['billing']['attribute']['text1']);
        $this->assertEquals($customer->getDefaultBillingAddress()->getAttribute()->getText1(), $testData['billing']['attribute']['text1']);

        $this->assertEquals($customer->getDefaultShippingAddress()->getFirstName(), $testData['shipping']['firstname']);
        $this->assertEquals($customer->getDefaultShippingAddress()->getFirstname(), $testData['shipping']['firstname']);

        $this->assertEquals($customer->getDefaultShippingAddress()->getAttribute()->getText1(), $testData['shipping']['attribute']['text1']);
        $this->assertEquals($customer->getDefaultShippingAddress()->getAttribute()->getText1(), $testData['shipping']['attribute']['text1']);

        //test additional address lines
        $this->assertEquals($customer->getDefaultShippingAddress()->getAdditionalAddressLine1(), $testData['shipping']['additionalAddressLine1']);
        $this->assertEquals($customer->getDefaultShippingAddress()->getAdditionalAddressLine2(), $testData['shipping']['additionalAddressLine2']);
        $this->assertEquals($customer->getDefaultBillingAddress()->getAdditionalAddressLine1(), $testData['billing']['additionalAddressLine1']);
        $this->assertEquals($customer->getDefaultBillingAddress()->getAdditionalAddressLine2(), $testData['billing']['additionalAddressLine2']);

        return $customer->getId();
    }

    /**
     * @depends testCreateShouldBeSuccessful
     */
    public function testGetOneShouldBeSuccessful($id)
    {
        $customer = $this->resource->getOne($id);
        $this->assertGreaterThan(0, $customer['id']);
    }

    /**
     * @depends testCreateShouldBeSuccessful
     */
    public function testGetOneByNumberShouldBeSuccessful($id)
    {
        $this->resource->setResultMode(Resource::HYDRATE_OBJECT);
        $customer = $this->resource->getOne($id);
        $number = $customer->getNumber();

        $customer = $this->resource->getOneByNumber($number);
        $this->assertEquals($id, $customer->getId());
    }

    /**
     * @depends testCreateShouldBeSuccessful
     */
    public function testGetOneShouldBeAbleToReturnObject($id)
    {
        $this->resource->setResultMode(Resource::HYDRATE_OBJECT);
        $customer = $this->resource->getOne($id);

        $this->assertInstanceOf('\Shopware\Models\Customer\Customer', $customer);
        $this->assertGreaterThan(0, $customer->getId());
    }

    /**
     * @depends testCreateShouldBeSuccessful
     */
    public function testGetListShouldBeSuccessful()
    {
        $result = $this->resource->getList();

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);

        $this->assertGreaterThanOrEqual(1, $result['total']);
        $this->assertGreaterThanOrEqual(1, $result['data']);
    }

    /**
     * @depends testCreateShouldBeSuccessful
     */
    public function testGetListShouldBeAbleToReturnObjects()
    {
        $this->resource->setResultMode(Resource::HYDRATE_OBJECT);
        $result = $this->resource->getList();

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);

        $this->assertGreaterThanOrEqual(1, $result['total']);
        $this->assertGreaterThanOrEqual(1, $result['data']);

        $this->assertInstanceOf('\Shopware\Models\Customer\Customer', $result['data'][0]);
    }

    /**
     * @expectedException \Shopware\Components\Api\Exception\ValidationException
     */
    public function testCreateWithInvalidDataShouldThrowValidationException()
    {
        $testData = [
            'active' => true,
            'email' => 'invalid',
            'billing' => [
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'country' => 2,
            ],
        ];

        $this->resource->create($testData);
    }

    /**
     * @expectedException \Shopware\Components\Api\Exception\ValidationException
     * @expectedExceptionMessageRegExp "salutation: The value you selected is not a valid choice."
     */
    public function testCreateWithInvalidDataShouldThrowValidationExceptionWithCorrectToStringMessage()
    {
        $testData = [
            'active' => true,
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'email' => 'max@mustermann.de',
            'salutation' => 'max',
            'billing' => [
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'country' => 2,
            ],
        ];

        $this->resource->create($testData);
    }

    /**
     * @depends testCreateShouldBeSuccessful
     */
    public function testUpdateShouldBeSuccessful($id)
    {
        $testData = [
            'active' => true,
            'email' => Random::getAlphanumericString(5) . 'update@foobar.com',
            'billing' => [
                'firstname' => 'Max Update',
                'lastname' => 'Mustermann Update',
                'additionalAddressLine1' => 'additional billing address Line 1',
                'additionalAddressLine2' => 'additional billing address Line 2',
            ],
            'shipping' => [
                'additionalAddressLine1' => 'additional shipping address Line 1',
                'additionalAddressLine2' => 'additional shipping address Line 2',
            ],
        ];

        $customer = $this->resource->update($id, $testData);

        $this->assertInstanceOf('\Shopware\Models\Customer\Customer', $customer);
        $this->assertEquals($id, $customer->getId());

        $this->assertEquals($customer->getEmail(), $testData['email']);
        $this->assertEquals($customer->getDefaultBillingAddress()->getFirstName(), $testData['billing']['firstname']);

        //test additional fields
        $this->assertEquals($customer->getDefaultBillingAddress()->getAdditionalAddressLine1(), $testData['billing']['additionalAddressLine1']);
        $this->assertEquals($customer->getDefaultBillingAddress()->getAdditionalAddressLine2(), $testData['billing']['additionalAddressLine2']);

        $this->assertEquals($customer->getDefaultShippingAddress()->getAdditionalAddressLine1(), $testData['shipping']['additionalAddressLine1']);
        $this->assertEquals($customer->getDefaultShippingAddress()->getAdditionalAddressLine2(), $testData['shipping']['additionalAddressLine2']);

        return $id;
    }

    /**
     * @depends testCreateShouldBeSuccessful
     */
    public function testUpdateByNumberShouldBeSuccessful($id)
    {
        $this->resource->setResultMode(Resource::HYDRATE_OBJECT);
        $customer = $this->resource->getOne($id);
        $number = $customer->getNumber();

        $testData = [
            'active' => true,
            'email' => Random::getAlphanumericString(5) . 'update@foobar.com',
            'billing' => [
                'firstname' => 'Max Update',
                'lastname' => 'Mustermann Update',
            ],
        ];

        $customer = $this->resource->updateByNumber($number, $testData);

        $this->assertInstanceOf('\Shopware\Models\Customer\Customer', $customer);
        $this->assertEquals($id, $customer->getId());

        $this->assertEquals($customer->getEmail(), $testData['email']);
        $this->assertEquals($customer->getDefaultBillingAddress()->getFirstName(), $testData['billing']['firstname']);

        return $number;
    }

    /**
     * @depends testCreateShouldBeSuccessful
     * @expectedException \Shopware\Components\Api\Exception\ValidationException
     */
    public function testUpdateWithInvalidDataShouldThrowValidationException($id)
    {
        $testData = [
            'active' => true,
            'email' => 'invalid',
            'billing' => [
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
            ],
        ];

        $this->resource->update($id, $testData);
    }

    /**
     * @expectedException \Shopware\Components\Api\Exception\NotFoundException
     */
    public function testUpdateWithInvalidIdShouldThrowNotFoundException()
    {
        $this->resource->update(9999999, []);
    }

    /**
     * @expectedException \Shopware\Components\Api\Exception\ParameterMissingException
     */
    public function testUpdateWithMissingIdShouldThrowParameterMissingException()
    {
        $this->resource->update('', []);
    }

    /**
     * @depends testUpdateShouldBeSuccessful
     */
    public function testDeleteShouldBeSuccessful($id)
    {
        $customer = $this->resource->delete($id);

        $this->assertInstanceOf('\Shopware\Models\Customer\Customer', $customer);
        $this->assertEquals(null, $customer->getId());
    }

    /**
     * @expectedException \Shopware\Components\Api\Exception\NotFoundException
     */
    public function testDeleteWithInvalidIdShouldThrowNotFoundException()
    {
        $this->resource->delete(9999999);
    }

    /**
     * @expectedException \Shopware\Components\Api\Exception\ParameterMissingException
     */
    public function testDeleteWithMissingIdShouldThrowParameterMissingException()
    {
        $this->resource->delete('');
    }

    /**
     * @return int
     */
    public function testPostCustomersWithDebitShouldCreatePaymentData()
    {
        $date = new \DateTime();
        $date->modify('-10 days');
        $firstlogin = $date->format(\DateTime::ISO8601);

        $date->modify('+2 day');
        $lastlogin = $date->format(\DateTime::ISO8601);

        $birthday = \DateTime::createFromFormat('Y-m-d', '1986-12-20')->format(\DateTime::ISO8601);

        $requestData = [
            'password' => 'fooobar',
            'active' => true,
            'email' => Random::getAlphanumericString(5) . 'test1@foobar.com',

            'firstlogin' => $firstlogin,
            'lastlogin' => $lastlogin,

            'salutation' => 'mr',
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'birthday' => $birthday,

            'billing' => [
                'salutation' => 'mr',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'street' => 'Musterstraße 123',
                'zipcode' => 12345,
                'city' => 'Musterhausen',
                'country' => '2',
            ],

            'shipping' => [
                'salutation' => 'Mr',
                'company' => 'Widgets Inc.',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'street' => 'Musterstraße 123',
                'zipcode' => 12345,
                'city' => 'Musterhausen',
                'country' => '2',
            ],

            'debit' => [
                'account' => 'Fake Account',
                'bankCode' => '55555555',
                'bankName' => 'Fake Bank',
                'accountHolder' => 'Max Mustermann',
            ],
        ];

        $customer = $this->resource->create($requestData);
        $identifier = $customer->getId();

        $this->resource->getManager()->clear();
        $customer = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($identifier);

        $paymentData = array_shift($customer->getPaymentData()->toArray());

        $this->assertNotNull($paymentData);
        $this->assertEquals('Max Mustermann', $paymentData->getAccountHolder());
        $this->assertEquals('Fake Account', $paymentData->getAccountNumber());
        $this->assertEquals('Fake Bank', $paymentData->getBankName());
        $this->assertEquals('55555555', $paymentData->getBankCode());

        $this->testDeleteShouldBeSuccessful($identifier);
    }

    /**
     * @return int
     */
    public function testPostCustomersWithDebitPaymentDataShouldCreateDebitData()
    {
        $date = new \DateTime();
        $date->modify('-10 days');
        $firstlogin = $date->format(\DateTime::ISO8601);

        $date->modify('+2 day');
        $lastlogin = $date->format(\DateTime::ISO8601);

        $birthday = \DateTime::createFromFormat('Y-m-d', '1986-12-20')->format(\DateTime::ISO8601);

        $requestData = [
            'password' => 'fooobar',
            'active' => true,
            'email' => Random::getAlphanumericString(5) . 'test2@foobar.com',

            'firstlogin' => $firstlogin,
            'lastlogin' => $lastlogin,

            'salutation' => 'mr',
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'birthday' => $birthday,

            'billing' => [
                'salutation' => 'mr',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'street' => 'Musterstraße 123',
                'zipcode' => 12345,
                'city' => 'Musterhausen',
                'country' => '2',
            ],

            'shipping' => [
                'salutation' => 'Mr',
                'company' => 'Widgets Inc.',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'street' => 'Musterstraße 123',
                'zipcode' => 12345,
                'city' => 'Musterhausen',
                'country' => '2',
            ],

            'paymentData' => [
                [
                    'paymentMeanId' => 2,
                    'accountNumber' => 'Fake Account',
                    'bankCode' => '55555555',
                    'bankName' => 'Fake Bank',
                    'accountHolder' => 'Max Mustermann',
                ],
            ],
        ];

        $customer = $this->resource->create($requestData);
        $identifier = $customer->getId();

        $this->resource->getManager()->clear();
        $customer = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($identifier);

        $paymentData = array_shift($customer->getPaymentData()->toArray());

        $this->assertNotNull($paymentData);
        $this->assertEquals('Max Mustermann', $paymentData->getAccountHolder());
        $this->assertEquals('Fake Account', $paymentData->getAccountNumber());
        $this->assertEquals('Fake Bank', $paymentData->getBankName());
        $this->assertEquals('55555555', $paymentData->getBankCode());

        $this->testDeleteShouldBeSuccessful($identifier);
    }

    public function testCreateWithDifferentCustomerGroup()
    {
        $data = [
            'password' => 'fooobar',
            'email' => __FUNCTION__ . Random::getAlphanumericString(5) . '@foobar.com',
            'number' => __FUNCTION__,
            'salutation' => 'mr',
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'groupKey' => 'H',
            'billing' => [
                'salutation' => 'mr',
                'zipcode' => '12345',
                'city' => 'Musterhausen',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'street' => 'Musterstr. 123',
                'country' => '2',
            ],
        ];

        $customer = $this->resource->create($data);
        $this->assertEquals('H', $customer->getGroup()->getKey());
    }

    public function testCreateCustomerWithDefaultShopCustomerGroup()
    {
        $context = Shopware()->Container()->get('shopware_storefront.context_service')->createShopContext(1);
        $data = [
            'shopId' => 1,
            'password' => 'fooobar',
            'email' => __FUNCTION__ . Random::getAlphanumericString(5) . '@foobar.com',
            'number' => __FUNCTION__,
            'salutation' => 'mr',
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'billing' => [
                'salutation' => 'mr',
                'zipcode' => '12345',
                'city' => 'Musterhausen',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'street' => 'Musterstr. 123',
                'country' => '2',
            ],
        ];

        $customer = $this->resource->create($data);
        $this->assertEquals($context->getShop()->getCustomerGroup()->getKey(), $customer->getGroup()->getKey());
    }

    /**
     * @group failing
     */
    public function testCreateCustomerCreatesCustomerAttribute()
    {
        $data = [
            'email' => __FUNCTION__ . Random::getAlphanumericString(5) . '@foobar.com',
            'number' => __FUNCTION__,
            'salutation' => 'mr',
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'billing' => [
                'salutation' => 'mr',
                'zipcode' => '12345',
                'city' => 'Musterhausen',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'street' => 'Musterstr. 123',
                'country' => '2',
            ],
        ];

        $customer = $this->resource->create($data);

        $this->assertNotNull($customer->getAttribute());
        $this->assertInstanceOf(CustomerAttribute::class, $customer->getAttribute());
    }
}
