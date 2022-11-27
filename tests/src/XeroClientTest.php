<?php

namespace Jprasmussen\Tests\Xero;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Jprasmussen\Xero\Exception\InvalidOptionsException;
use Jprasmussen\Xero\XeroClient;

/**
 * Tests for the XeroClient class.
 *
 * @group xeroclient
 */
class XeroClientTest extends XeroClientTestBase
{

    /**
     * @param array $options
     *   Invalid options to pass to the consructor.
     *
     * @dataProvider invalidOptionsExceptionProvider
     */
    public function testInvalidOptionsException($options)
    {
        $this->expectException(InvalidOptionsException::class);
        $client = new XeroClient($options);

        $this->assertNull($client);
    }

    /**
     * Asserts private application instantiation.
     */
    public function testPrivateApplication()
    {
        $options = $this->createConfiguration();
        $client = new XeroClient($options);
        $this->assertNotNull($client);
    }

    /**
     * Asserts public application instantiation.
     */
    public function testPublicApplication()
    {
        $options = $this->createConfiguration('accounting', 'public');
        $client = new XeroClient($options);
        $this->assertNotNull($client);
    }

    /**
     * Asserts get request token.
     */
    public function testGetRequestToken()
    {
        $options = $this->createConfiguration('accounting', 'public');
        $expected = ['oauth_token' => $options['token'], 'oauth_secret' => $options['token_secret']];
        $response = 'oauth_token=' . $options['token'] . '&oauth_secret=' . $options['token_secret'];
        $mock = new MockHandler(
            [
                new Response(200, ['Content-Type' => 'application/x-www-form-urlencoded'], $response)
            ]
        );
        $options['handler'] = new HandlerStack($mock);

        $tokens = XeroClient::getRequestToken($options['consumer_key'], $options['consumer_secret'], $options);
        $this->assertEquals($expected, $tokens);
    }

    /**
     * Asserts getting an access token.
     */
    public function testGetAccessToken()
    {
        $expected = [
            'oauth_token' => $this->createRandomString(),
            'oauth_secret' => $this->createRandomString(),
        ];

        $options = $this->createConfiguration('accounting', 'public');
        $response = 'oauth_token=' . $expected['oauth_token'] . '&oauth_secret=' . $expected['oauth_secret'];
        $mock = new MockHandler(
            [
                new Response(200, ['Content-Type' => 'application/x-www-form-urlencoded'], $response)
            ]
        );
        $options['callback'] = 'https://example.com';
        $options['handler'] = new HandlerStack($mock);

        $tokens = XeroClient::getAccessToken(
            $options['consumer_key'],
            $options['consumer_secret'],
            $options['token'],
            $options['token_secret'],
            $options['verifier'],
            $options
        );
        $this->assertEquals($expected, $tokens);
    }

    /**
     * @param $statusCode
     * @param $headers
     * @param $body
     *
     * @dataProvider providerGetTest
     *
     * @throws \Jprasmussen\Xero\Exception\InvalidOptionsException
     */
    public function testGet($statusCode, $headers, $body)
    {
        $options = $this->createConfiguration();
        $mock = new MockHandler(
            [
                new Response($statusCode, $headers, $body)
            ]
        );
        $options['handler'] = new HandlerStack($mock);

        $client = new XeroClient($options);

        $response = $client->get('/BrandingThemes');
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Tests trying to use an unreadable file.
     */
    public function testUnreadableFile()
    {
        $this->expectException(InvalidOptionsException::class);
        $path = __DIR__ . '/../fixtures/notreadable.pem';
        $options = [
            'base_uri' => 'https://api.xero.com/api.xro/2.0/',
            'consumer_key' => 'test',
            'consumer_secret' => 'test',
            'private_key' => $path,
            'application' => 'private',
        ];
        chmod($path, 000);
        $client = new XeroClient($options);
        $this->assertNull($client);

        // Test cleanup.
        chmod($path, 0644);
    }

    /**
     * Asserts that connections are returned and decoded.
     *
     * @param int $statusCode
     *   The HTTP status code to mock.
     * @param array $response
     *   The response body to encode as json.
     * @param int $expectedCount
     *   The expected number of connections.
     *
     * @dataProvider connectionsResponseProvider
     * @throws \Jprasmussen\Xero\Exception\InvalidOptionsException
     */
    public function testGetConnections($statusCode, array $response, $expectedCount)
    {
        $mock = new MockHandler([
            new Response($statusCode, [
              'Content-Type' => 'application/json',
            ], json_encode($response)),
        ]);
        $client = new XeroClient([
          'base_uri' => 'https://api.xero.com/connections',
          'scheme' => 'oauth2',
          'auth_token' => $this->createRandomString(),
          'handler' => new HandlerStack($mock),
        ]);

        $connections = $client->getConnections();
        $this->assertEquals($expectedCount, count($connections));
    }

    /**
     * @return array
     */
    public function invalidOptionsExceptionProvider()
    {
        return [
            [[]],
            [['base_uri' => '', 'application' => 'private']],
            [
                [
                    'base_uri' => 'https://api.xero.com/api.xro/2.0/',
                    'application' => 'private',
                ],
            ],
            [
                [
                    'base_uri' => 'https://api.xero.com/api.xro/2.0/',
                    'consumer_key' => '',
                    'application' => 'private',
                ],
            ],
            [
                [
                    'base_uri' => 'https://api.xero.com/api.xro/2.0/',
                    'consumer_key' => 'test',
                    'application' => 'private',
                ],
            ],
            [
                [
                    'base_uri' => 'https://api.xero.com/api.xro/2.0/',
                    'consumer_key' => 'test',
                    'consumer_secret' => '',
                    'application' => 'private',
                ],
            ],
            [
                [
                    'base_uri' => 'https://api.xero.com/api.xro/2.0/',
                    'consumer_key' => 'test',
                    'consumer_secret' => 'test',
                    'application' => 'private',
                ],
            ],
            [
                [
                    'base_uri' => 'https://api.xero.com/api.xro/2.0/',
                    'consumer_key' => 'test',
                    'consumer_secret' => 'test',
                    'private_key' => '',
                    'application' => 'private',
                ],
            ],
            [
                [
                    'base_uri' => 'https://api.xero.com/api.xro/2.0/',
                    'consumer_key' => 'test',
                    'consumer_secret' => 'test',
                    'private_key' => 'nofile.pem',
                    'application' => 'private',
                ],
            ],
            [
                [
                    'base_uri' => 'https://api.xero.com/api.xro/2.0/',
                    'consumer_key' => 'test',
                    'consumer_secret' => 'test',
                    'private_key' => 'testfile.pem',
                    'application' => 'private',
                ],
            ],
            [
                [
                    'base_uri' => 'https://api.xero.com/api.xro/2.0/',
                    'consumer_key' => 'test',
                    'consumer_secret' => 'test',
                    'private_key' => __DIR__,
                    'application' => 'private',
                ],
            ],
        ];
    }

    /**
     * Provide responses for get method.
     */
    public function providerGetTest()
    {
        return [
            [
                200,
                ['Content-Type' => 'text/xml'],
                '<?xml encoding="UTF-8" version="1.0"?><BrandingThemes><BrandingTheme><BrandingThemeID>' .
                $this->createGuid() .
                '</BrandingThemeID><Name>Standard</Name><SortOrder>0</SortOrder><CreatedDateUTC>' .
                '2010-06-29T18:16:36.27</CreatedDateUTC></BrandingTheme></BrandingThemes>',
            ]
        ];
    }

    /**
     * Test responses for the connections endpoint.
     *
     * @return array
     *   An array of test cases and arguments.
     */
    public function connectionsResponseProvider()
    {
        return [
            'returns tenants' => [200, [
                [
                    'id' => $this->createGuid(),
                    'tenantId' => $this->createGuid(),
                    'tenantType' => 'ORGANISATION',
                    'createdDateUtc' => '2020-02-02T19:17:58.1117990',
                    'updatedDateUtc' => '2020-02-02T19:17:58.1117990',
                ],
                [
                  'id' => $this->createGuid(),
                  'tenantId' => $this->createGuid(),
                  'tenantType' => 'ORGANISATION',
                  'createdDateUtc' => '2020-01-30T01:33:36.2717380',
                  'updatedDateUtc' => '2020-02-02T19:21:08.5739590',
                ],
            ], 2],
        ];
    }
}
