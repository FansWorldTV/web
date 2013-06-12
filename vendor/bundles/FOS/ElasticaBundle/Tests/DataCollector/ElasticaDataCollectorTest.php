<?php

namespace FOS\ElasticaBundle\Tests\DataCollector;

use FOS\ElasticaBundle\DataCollector\ElasticaDataCollector;

/**
 * @author Richard Miller <info@limethinking.co.uk>
 */
class ElasticaDataCollectorTest extends \PHPUnit_Framework_TestCase
{

    public function testCorrectAmountOfQueries()
    {
        $requestMock = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $responseMock = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $loggerMock = $this->getMockBuilder('FOS\ElasticaBundle\Logger\ElasticaLogger')
            ->disableOriginalConstructor()
            ->getMock();

        $totalQueries = rand();

        $loggerMock->expects($this->once())
            ->method('getNbQueries')
            ->will($this->returnValue($totalQueries));

        $elasticaDataCollector = new ElasticaDataCollector($loggerMock);
        $elasticaDataCollector->collect($requestMock, $responseMock);
        $this->assertEquals($totalQueries, $elasticaDataCollector->getQueryCount());
    }

    public function testCorrectQueriesReturned()
    {
        $requestMock = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $responseMock = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $loggerMock = $this->getMockBuilder('FOS\ElasticaBundle\Logger\ElasticaLogger')
            ->disableOriginalConstructor()
            ->getMock();

        $queries = array('testQueries');

        $loggerMock->expects($this->once())
            ->method('getQueries')
            ->will($this->returnValue($queries));

        $elasticaDataCollector = new ElasticaDataCollector($loggerMock);
        $elasticaDataCollector->collect($requestMock, $responseMock);
        $this->assertEquals($queries, $elasticaDataCollector->getQueries());
    }

}
