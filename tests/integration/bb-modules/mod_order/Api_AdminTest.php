<?php
/**
 * @group Core
 */
class Api_Admin_OrderTest extends BBDbApiTestCase
{
    protected $_initialSeedFile = 'orders.xml';

    public function testMetaUpdate()
    {
        $meta = array(
            'unique'  =>  'search by this field',
            'param_1'  =>  'value 1',
            'param_2'  =>  'value 2',
            'param_3'  =>  'value 3',
        );

        $bool = $this->api_admin->order_update(array('id'=>'1', 'meta'=>$meta));
        $this->assertTrue($bool);
        $order = $this->api_admin->order_get(array('id'=>1));
        $this->assertInternalType('array', $order);
        $this->assertTrue(isset($order['meta']));
        $this->assertEquals('value 1', $order['meta']['param_1']);
        $this->assertEquals('value 2', $order['meta']['param_2']);
        $this->assertEquals('value 3', $order['meta']['param_3']);

        //search test
        $array = $this->api_admin->order_get_list(array('meta'=> array('unique'=>'search by this')));
        $this->assertEquals(1, $array['total']);
        $this->assertEquals('search by this field', $array['list'][0]['meta']['unique']);
    }

    public static function orders()
    {
        return array(
            array(1),
            array(2),
            array(3),
            array(4),
            array(5),
            array(6),
            array(7),
            array(9),
            //array(12), // solusvm
            //array(13), // serviceboxbillinglicense
        );
    }

    /**
     * @dataProvider orders
     */
    public function testOrdersStatuses($id)
    {
        $data['id'] = $id;

        $order = $this->api_admin->order_get($data);
        $this->assertInternalType('array', $order);
        $this->assertEquals($id, $order['id']);

        $list = $this->api_admin->order_get_list(array());
        $this->assertInternalType('array', $list);

        $bool = $this->api_admin->order_update(array('id'=>1,'expires_at'=>date('c', strtotime('+ 2 days'))));
        $this->assertTrue($bool);

        $bool = $this->api_admin->order_renew($data);
        $this->assertTrue($bool);

        $bool = $this->api_admin->order_renew($data);
        $this->assertTrue($bool);

        $array = $this->api_admin->order_service($data);
        $this->assertInternalType('array', $array);

        $bool = $this->api_admin->order_suspend($data);
        $this->assertTrue($bool);

        $bool = $this->api_admin->order_unsuspend($data);
        $this->assertTrue($bool);

        $bool = $this->api_admin->order_suspend($data);
        $this->assertTrue($bool);

        $bool = $this->api_admin->order_renew($data);
        $this->assertTrue($bool);

        $bool = $this->api_admin->order_cancel($data);
        $this->assertTrue($bool);

        $bool = $this->api_admin->order_uncancel($data);
        $this->assertTrue($bool);

        $bool = $this->api_admin->order_cancel($data);
        $this->assertTrue($bool);

        $bool = $this->api_admin->order_renew($data);
        $this->assertTrue($bool);

        $data['config'] = array('foo'=>'bar');
        $bool = $this->api_admin->order_update_config($data);
        $this->assertTrue($bool);

        $list = $this->api_admin->order_status_history_get_list($data);
        $this->assertInternalType('array', $list);
        
        $array = $this->api_admin->order_addons($data);
        $this->assertInternalType('array', $array);

        $bool = $this->api_admin->order_delete($data);
        $this->assertTrue($bool);
    }

    public function testLists()
    {
        $array = $this->api_admin->order_get_invoice_options();
        $this->assertInternalType('array', $array);
        
        $array = $this->api_admin->order_get_status_pairs();
        $this->assertInternalType('array', $array);

        $array = $this->api_admin->order_get_statuses(array());
        $this->assertInternalType('array', $array);
    }

    public function testSuspendation()
    {
        $bool = $this->api_admin->order_batch_suspend_expired();
        $this->assertTrue($bool);
    }

    public function testCancellationOfSuspendedOrders()
    {
        $bool = $this->api_admin->order_batch_cancel_suspended();
        $this->assertTrue($bool);
    }

    public function testLicense()
    {
        $data['id'] = 3;
        $bool = $this->api_admin->order_renew($data);
        $this->assertTrue($bool);   
    }
    
    public function testDeleteAddons()
    {
        $data['id'] = 1;
        $addons = $this->api_admin->order_addons($data);
        $this->assertEquals(1, count($addons));
        $addon_id = $addons[0]['id'];
        
        $data['delete_addons'] = true;
        $bool = $this->api_admin->order_delete($data);
        $this->assertTrue($bool);   

        try {
            $this->api_admin->order_get(array('id'=>$addon_id));
            $this->fail('Order addon should be removed');
        } catch(Exception $e) {
        }
    }

    public function testOrderExpiration()
    {
        $data = array(
            'id'            => 8,
            'period'        => '2Y',
            'expires_at'    => date('c', strtotime('2012-01-10')),
        );
        $this->api_admin->order_update($data);
        $ob = $this->api_admin->order_get($data);
        $this->api_admin->order_renew($data);
        $oa = $this->api_admin->order_get($data);
        
        $this->assertEquals(2, date('Y', strtotime($oa['expires_at'])) - date('Y', strtotime($ob['expires_at'])));
    }

    public function testDomainOrderExpiration()
    {
        $data['id'] = 7;
        $bool = $this->api_admin->order_renew($data);
        $this->assertTrue($bool);

        $order = $this->api_admin->order_get($data);
        $this->assertInternalType('array', $order);

        $this->assertTrue(!is_null($order['expires_at']), 'Domain Order expiration date was not set after activation');
    }

    public static function products()
    {
        return array(
            array(1, array()), //custom
            array(6, array()), //license
            array(7, array()), //downloadable
            array(10, array('action'=>'register', 'register_sld'=>'test', 'register_tld'=>".com", 'register_years'=>'3')), //domain
            array(10, array('action'=>'transfer', 'transfer_sld'=>'test', 'transfer_tld'=>".com", 'transfer_code'=>'asdasd')), //domain
            array(10, array('action'=>'owndomain', 'owndomain_sld'=>'test', 'owndomain_tld'=>".com", 'register_years'=>'3')), //domain
            array(12, array('some'=>'var')), //membership
            array(8, array('domain'=>array('action'=>'owndomain', 'owndomain_sld'=>'cololo', 'owndomain_tld'=>'.com'))), //hosting
            
            array(3, array()), //addon
        );
    }

    /**
     * @dataProvider products
     */
    public function testCreate($pid, $config)
    {
        $data['client_id']      = 1;
        $data['product_id']     = $pid;
        $data['period']         = '1M';
        $data['group_id']       = 2;
//        $data['currency']       = 'EUR';
//        $data['invoice_option'] = 'issue-invoice';
        $data['invoice_option'] = 'no-invoice';
        $data['activate'] = 1;
        $data['config'] = $config;
        
        $id = $this->api_admin->order_create($data);
        $this->assertInternalType('int', $id);
    }
    
    /**
     * Test recurent promo for order
     * 1. If promo is recurrent then new invoice is generated with discount
     * 2. If promo is not recurrent then new invoice is generated for order total price
     */
    public function testPromoRec()
    {
        $data['id']      = 8; //order with recurring promo
        $id = $this->api_admin->invoice_renewal_invoice($data);
        $invoice = $this->api_admin->invoice_get(array('id'=>$id));
        $this->assertEquals(15 ,$invoice['lines'][0]['total']);
        
        $data['id']      = 10; //order without recurring promo
        $id = $this->api_admin->invoice_renewal_invoice($data);
        $invoice = $this->api_admin->invoice_get(array('id'=>$id));
        $this->assertEquals(30 ,$invoice['lines'][0]['total']);
    }

    public function testHistory()
    {
        $data = array(
            'id' => 1,
            'status' => 'cancelled by phpUnit',
        );
        $result = $this->api_admin->order_status_history_add($data);
        $this->assertTrue($result);

        $data = array(
            'id' => 1,
        );
        $result = $this->api_admin->order_status_history_delete($data);
        $this->assertTrue($result);
    }

    public function testOrderBatchDelete()
    {
        $array  = $this->api_admin->order_get_list(array());

        foreach ($array['list'] as $value){
            $ids[] = $value['id'];
        }
        $result = $this->api_admin->order_batch_delete(array('ids' => $ids, 'delete_addons' => true));
        $array  = $this->api_admin->order_get_list(array());
        $this->assertEquals(0, count($array['list']));
        $this->assertTrue($result);
    }

    public function testcreateMeteredProductOrder()
    {
        $data['client_id']      = 1;
        $data['product_id']     = 17;
        $data['group_id']       = 2;
        $data['invoice_option'] = 'no-invoice';
        $data['activate'] = 1;
        $data['config'] = array('domain'=>array('action'=>'owndomain', 'owndomain_sld'=>'vm', 'owndomain_tld'=>'.com'));

        $id = $this->api_admin->order_create($data);
        $this->assertInternalType('int', $id);

        $orderModel = $this->di['db']->load("ClientOrder", $id);
        $orderService = $this->di['mod_service']('Order');
        $result = $orderService->haveMeteredBilling($orderModel);
        $this->assertTrue($result);
    }

    public function testChangeMeteredProductinOrder()
    {
        $data['client_id']      = 1;
        $data['product_id']     = 17;
        $data['group_id']       = 2;
        $data['invoice_option'] = 'no-invoice';
        $data['activate']       = 1;
        $data['config']         = array('domain' => array('action' => 'owndomain', 'owndomain_sld' => 'changeProductorder', 'owndomain_tld' => '.com'));

        $id = $this->api_admin->order_create($data);

        $newProductId = 18;
        $data         = array(
            'id'         => $id,
            'product_id' => $newProductId,
        );
        $bool         = $this->api_admin->order_change_order_product($data);
        $this->assertTrue($bool);

        $orderModel = $this->di['db']->load("ClientOrder", $id);
        $this->assertEquals($newProductId, $orderModel->product_id);
    }

    public function testChangeMeteredProductinOrder_DifferentType()
    {
        $data['client_id']      = 1;
        $data['product_id']     = 17;
        $data['group_id']       = 2;
        $data['invoice_option'] = 'no-invoice';
        $data['activate'] = 1;
        $data['config'] = array('domain'=>array('action'=>'owndomain', 'owndomain_sld'=>'changeProductorder', 'owndomain_tld'=>'.com'));

        $id = $this->api_admin->order_create($data);

        $newProductId = 7;
        $data = array(
            'id' => $id,
            'product_id' => $newProductId,
        );

        $this->setExpectedException('\Box_Exception', 'Order product can not be changed');
        $this->api_admin->order_change_order_product($data);
    }

    public function testChangeMeteredProductinOrder_NotMeteredPayment()
    {
        $data['client_id']      = 1;
        $data['product_id']     = 17;
        $data['group_id']       = 2;
        $data['invoice_option'] = 'no-invoice';
        $data['activate'] = 1;
        $data['config'] = array('domain'=>array('action'=>'owndomain', 'owndomain_sld'=>'changeProductorder', 'owndomain_tld'=>'.com'));

        $id = $this->api_admin->order_create($data);

        $newProductId = 8;
        $data = array(
            'id' => $id,
            'product_id' => $newProductId,
        );

        $this->setExpectedException('\Box_Exception', 'Order product can not be changed');
        $this->api_admin->order_change_order_product($data);
    }

    public function testmeteredBillingOrderSuspendAndUnsuspend()
    {
        $data['client_id']      = 1;
        $data['product_id']     = 17;
        $data['group_id']       = 2;
        $data['invoice_option'] = 'no-invoice';
        $data['activate'] = 1;
        $data['config'] = array('domain'=>array('action'=>'owndomain', 'owndomain_sld'=>'changeProductorder', 'owndomain_tld'=>'.com'));

        $id = $this->api_admin->order_create($data);

        sleep(1);

        $data = array(
            'id' => $id,
        );
        $this->api_admin->order_suspend($data);

        sleep(1);

        $this->api_admin->order_unsuspend($data);

        $meteredUsaged = $this->di['db']->findOne('MeteredUsage', 'Order by id desc');
        $this->assertEquals(0, $meteredUsaged->quantity);
    }

}