<?php
namespace MultiCurlTest;

use MultiHttp\Request;
use MultiHttp\Response;

/**
 *
 * @author  qiangjian@staff.sina.com.cn sunsky303@gmail.com
 * Date: 2017/6/15
 * Time: 18:31
 * @version $Id: $
 * @since 1.0
 * @copyright Sina Corp.
 */

class RequestTest extends \PHPUnit_Framework_TestCase
{
    public static function setupBeforeClass()
    {
    }
    private $inst;
    protected function setUp()
    {
        parent::setUp();
    }
    function test()
    {
        $start = microtime(1);
        $responses = array();
        $responses[] = Request::create()->addQuery('sleepSecs=2')->trace(TEST_SERVER.'/dynamic/blocking.php', array(
            'timeout' => 3,
            'timeout_ms' => 2000,
            'callback' => function (Response $response) {
                $this->assertLessThan(3,$response->duration);
                $this->assertGreaterThan(2,$response->duration);
                $this->assertTrue($response->hasErrors(), $response->error);
                $this->assertFalse($response->body);
                $this->assertEquals(TEST_SERVER.'/dynamic/blocking.php?sleepSecs=2', $response->request->getURI());
            }))->execute();

        $responses[] = Request::create()->addQuery(array('sleepSecs'=>2))->trace(TEST_SERVER.'/dynamic/blocking.php', array(
            'timeout_ms' => 2000,
            'timeout' => 3,
            'callback' => function (Response $response) {
                $this->assertLessThan(3,$response->duration);
                $this->assertGreaterThan(2,$response->duration);
                $this->assertTrue (strlen($response->body)>0);
                $this->assertFalse($response->hasErrors());
                $this->assertEquals(TEST_SERVER.'/dynamic/blocking.php?sleepSecs=2', $response->request->getURI());
            }))->execute();

        $responses[] = Request::create()->trace(TEST_SERVER.'/static/test.json')->onEnd(function (Response $response) {
            $this->assertFalse($response->hasErrors());
            $this->assertEquals(TEST_SERVER.'/static/test.json', $response->request->getURI());
            $this->assertTrue (strlen($response->body)>0);
            $this->assertJsonStringEqualsJsonFile(WEB_SERVER_DOCROOT.'/static/test.json', $response->body);
        })->execute();

        $responses[] = Request::create()->get(TEST_SERVER.'/dynamic/blocking.php?sleepSecs=0', array(
            'callback' => function (Response $response) {
                $this->assertTrue ($response->request->hasEndCallback());
            }))->execute();

        $responses[] = Request::create()->get(TEST_SERVER.'/dynamic/blocking.php?sleepSecs=0', array(
            'callback' => function (Response $response) {
                $this->assertEquals( Request::PATCH,$response->request->getIni('method'));
                $this->assertTrue ($response->request->hasEndCallback());
            }))->addOptions(array(
            'method' => Request::PATCH
        ))->execute();

        //test post
        $responses[] = Request::create()->post(TEST_SERVER.'/dynamic/blocking.php',array('data'=>'this_is_post_data'), array(
            'callback' => function (Response $response) {
                $this->assertEquals( Request::POST,$response->request->getIni('method'));
                $this->assertTrue ($response->request->hasEndCallback());
                $this->assertContains('this_is_post_data', $response->body);

            }))->execute();
        $responses[] = Request::create()->post(TEST_SERVER.'/dynamic/blocking.php',array(), array(
//            'data' => 'data=this_is_post_data', //not work
            'callback' => function (Response $response) {
                $this->assertEquals( Request::POST,$response->request->getIni('method'));
                $this->assertTrue ($response->request->hasEndCallback());
                $this->assertNotContains('this_is_post_data', $response->body);

            }))->execute();
        $response = Request::create()->head(TEST_SERVER.'/dynamic/blocking.php?head', array(
            'callback' => function (Response $response) {
            }
        ))->applyOptions()->makeResponse();
        $this->assertInstanceOf( '\MultiHttp\Response',$response);
        $this->assertEmpty($response->body);
        $this->assertNotEmpty($response->header);

        echo "\n\t\n\texec done\n\t\n\t";
        foreach ($responses as $response) {
            echo $response->request->getURI(), ' takes:', $response->duration,  "\n\t\n\t";
        }
        $end = microtime(1);
        echo 'total takes:', $end-$start, ' secs;';

    }
}
