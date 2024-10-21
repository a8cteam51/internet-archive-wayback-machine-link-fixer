# TestCase Traits

There are a number of WP_Unit_Testcase traits that can be used to test your code. These allow for easier use of working with user and wc data, and also allow for easier testing of the output of your code.

## User_Factory_Trait

This allows for a quick and easy way to create users, and also allows for the creation of users with specific roles.

```php
class Test_Foo extends WP_UnitTestCase{
    use User_Factory_Trait;

    public function test_with_admin_user(){
        $administrator = $this->create_admin_user();
        $this->assertInstanceOf('WP_User', $administrator);
        $this->assertEquals('administrator', $administrator->roles[0]);
    }

    public function test_with_subscriber_user(){
        $subscriber = $this->create_subscriber_user();
        $this->assertInstanceOf('WP_User', $subscriber);
        $this->assertEquals('subscriber', $subscriber->roles[0]);
    }

    public function test_with_customer(){
        $customer = $this->create_customer_user();
        $this->assertInstanceOf('WP_User', $customer);
        $this->assertEquals('customer', $customer->roles[0]);
    }
}
```

## Product_Factory_Trait

This allows for a quick and easy way to create products, and also allows for the creation of products with specific types.

```php
class Test_Foo extends WP_UnitTestCase{
    use Product_Factory_Trait;

    public function test_with_simple_product(){
        $product = $this->create_simple_product();
        $this->assertInstanceOf('WC_Product_Simple', $product);
    }

    public function test_with_modified_simple_product(){
        $product = $this->create_variable_product(
            function(WC_Product $product): WC_Product{
                $product->set_regular_price(999.99);
                $product->set_tax_status('taxable');
                return $product;
            }
        );
        $this->assertInstanceOf('WC_Product_Simple', $product);
        $this->assertEquals(999.99, $product->get_regular_price());
        $this->assertEquals('taxable', $product->get_tax_status());
    }
}
```