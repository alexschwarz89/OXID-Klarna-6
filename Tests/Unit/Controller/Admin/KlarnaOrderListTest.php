<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Controller\Admin\KlarnaOrderList;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaOrderListTest extends ModuleUnitTestCase
{
    protected function setOrder($exception = null)
    {
        $order = $this->getMock(
            Order::class,
            ['isLoaded', 'cancelKlarnaOrder', 'save', 'isKlarnaOrder', 'isDerived', 'delete']
        );
        $order->expects($this->any())->method('isLoaded')->willReturn(true);
        $order->expects($this->any())->method('save')->willReturn(true);
        $order->expects($this->any())->method('isKlarnaOrder')->willReturn(true);
        $order->expects($this->any())->method('isDerived')->willReturn(true);
        $order->expects($this->any())->method('delete')->willReturn(true);

        if ($exception) {
            $order->expects($this->any())->method('cancelKlarnaOrder')->willThrowException($exception);
        } else {
            $order->expects($this->any())->method('cancelKlarnaOrder')->willReturn(true);
        }
        $order->oxorder_oxbillcountryid = new Field('a7c40f631fc920687.20179984', Field::T_RAW);
        $order->oxorder__tcklarna_orderid = new Field('1', Field::T_RAW);
        \oxTestModules::addModuleObject(Order::class, $order);

        return $order;
    }

    /**
     * @dataProvider stornoAndDeleteDataProvider
     * @param $method
     */
    public function testStornoAndDelete($method)
    {
        $order = $this->setOrder();

        $controller = $this->createStub(KlarnaOrderList::class, ['getEditObjectId' => 'test', 'cancelOrder' => true, 'resetContentCache' => true, 'init' => true]);

        $this->assertFalse($order->oxorder__tcklarna_sync);
        $controller->$method();
        $this->assertEquals(new Field(1), $order->oxorder__tcklarna_sync);

        if ($method == 'storno') {
            $mockException = $this->getMock(StandardException::class, [], ['is canceled.']);
            $this->setOrder($mockException);
            $controller->storno();
        }

        $mockException = $this->getMock(StandardException::class, [], ['test']);
        $this->setOrder($mockException);
        $controller->$method();

        $result = unserialize($this->getSessionParam('Errors')['default'][0]);
        $this->assertInstanceOf(ExceptionToDisplay::class, $result);
        $this->assertEquals('test', $result->getOxMessage());

    }

    public function stornoAndDeleteDataProvider()
    {
        return [
            ['storno'],
            ['deleteEntry'],
        ];

    }
}