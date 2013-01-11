<?php

namespace Purl\Test;

use PHPUnit_Framework_TestCase;
use Purl\Url;

class UrlTest extends PHPUnit_Framework_TestCase
{
    public function testParseSanity()
    {
        $url = new Url('https://host.com:443/path with spaces?param1 with spaces=value1 with spaces&param2=value2#fragment1/fragment2 with spaces?param1=value1&param2 with spaces=value2 with spaces');
        $this->assertEquals('https', $url->scheme);
        $this->assertEquals('host.com', $url->host);
        $this->assertEquals('443', $url->port);
        $this->assertInstanceOf('Purl\Path', $url->path);
        $this->assertEquals('/path%20with%20spaces', (string) $url->path);
        $this->assertInstanceOf('Purl\Query', $url->query);
        $this->assertEquals('param1_with_spaces=value1+with+spaces&param2=value2', (string) $url->query);
        $this->assertInstanceOf('Purl\Fragment', $url->fragment);
        $this->assertEquals('fragment1/fragment2%20with%20spaces?param1=value1&param2_with_spaces=value2+with+spaces', (string) $url->fragment);
        $this->assertInstanceOf('Purl\Path', $url->fragment->path);
        $this->assertInstanceOf('Purl\Query', $url->fragment->query);
        $this->assertEquals('param1=value1&param2_with_spaces=value2+with+spaces', (string) $url->fragment->query);
        $this->assertEquals('fragment1/fragment2%20with%20spaces', (string) $url->fragment->path);
    }

    public function testParseStaticMethod()
    {
        $url = Url::parse('http://google.com');
        $this->assertInstanceOf('Purl\Url', $url);
        $this->assertEquals('http://google.com/', (string) $url);
    }

    public function testBuild()
    {
        $url = Url::parse('http://jwage.com')
            ->set('port', '443')
            ->set('scheme', 'https');
        
        $url->query
            ->set('param1', 'value1')
            ->set('param2', 'value2');

        $url->path->add('about');
        $url->path->add('me');

        $url->fragment->path->add('fragment1');
        $url->fragment->path->add('fragment2');

        $url->fragment->query
            ->set('param1', 'value1')
            ->set('param2', 'value2');

        $this->assertEquals('https://jwage.com/about/me?param1=value1&param2=value2#/fragment1/fragment2?param1=value1&param2=value2', (string) $url);
    }

    public function testJoin()
    {
        $url = new Url('http://jwage.com');
        $url->join('about?param=value#fragment');
        $this->assertEquals('http://jwage.com/about?param=value#fragment', (string) $url);
        $url->join(new Url('http://about.me/jwage'));
        $this->assertEquals('http://about.me/jwage?param=value#fragment', (string) $url);
    }

    public function testSetPath()
    {
        $url = new Url('http://jwage.com');
        $url->path = 'about';
        $this->assertInstanceOf('Purl\Path', $url->path);
        $this->assertEquals('about', (string) $url->path);
    }

    public function testSetQuery()
    {
        $url = new Url('http://jwage.com');
        $url->query->set('param1', 'value1');
        $this->assertInstanceOf('Purl\Query', $url->query);
        $this->assertEquals('param1=value1', (string) $url->query);
        $this->assertEquals(array('param1' => 'value1'), $url->query->getData());
    }

    public function testSetFragment()
    {
        $url = new Url('http://jwage.com');
        $url->fragment->path = 'about';
        $url->fragment->query->set('param1', 'value1');
        $this->assertEquals('http://jwage.com/#about?param1=value1', (string) $url);
    }

    public function testGetNetloc()
    {
        $url = new Url('https://user:pass@jwage.com:443');
        $this->assertEquals('user:pass@jwage.com:443', $url->getNetloc());
    }

    public function testGetUrl()
    {
        $url = new Url('http://jwage.com');
        $this->assertEquals('http://jwage.com/', $url->getUrl());
    }

    public function testSetUrl()
    {
        $url = new Url('http://jwage.com');
        $this->assertEquals('http://jwage.com/', $url->getUrl());
        $url->setUrl('http://google.com');
        $this->assertEquals('http://google.com/', $url->getUrl());
    }

    public function testArrayAccess()
    {
        $url = new Url('http://jwage.com');
        $url['path'] = 'about';
        $this->assertEquals('http://jwage.com/about', (string) $url);
    }

    public function testCanonicalization()
    {
        $url = new Url('http://jwage.com');
        $this->assertEquals('com', $url->suffix);
        $this->assertEquals('jwage', $url->domain);
        $this->assertEquals('com.jwage', $url->canonical);

        $url = new Url('http://sub.domain.jwage.com/index.php?param1=value1');
        $this->assertEquals('com', $url->suffix);
        $this->assertEquals('jwage', $url->domain);
        $this->assertEquals('sub.domain', $url->subdomain);
        $this->assertEquals('com.jwage.domain.sub/index.php?param1=value1', $url->canonical);

        $url = new Url('http://sub.domain.jwage.co.uk/index.php?param1=value1');
        $this->assertEquals('co.uk', $url->suffix);
        $this->assertEquals('jwage', $url->domain);
        $this->assertEquals('sub.domain', $url->subdomain);
        $this->assertEquals('uk.co.jwage.domain.sub/index.php?param1=value1', $url->canonical);
    }

    public function testPath()
    {
        $url = new Url('http://jwage.com');
        $url->path->add('about')->add('me');
        $this->assertEquals('http://jwage.com/about/me', (string) $url);
        $url->path->setPath('new/path');
        $this->assertEquals('http://jwage.com/new/path', (string) $url);
    }

    public function testFragment()
    {
        $url = new Url('http://jwage.com');
        $url->fragment = 'test';
        $url->fragment->path->add('about')->add('me');
        $url->fragment->query->set('param1', 'value1');
        $this->assertEquals('http://jwage.com/#test/about/me?param1=value1', (string) $url);

        $url->fragment = 'test/aboutme?param1=value1';
        $this->assertEquals('test/aboutme', (string) $url->fragment->path);
        $this->assertEquals('param1=value1', (string) $url->fragment->query);
    }

    public function testQuery()
    {
        $url = new Url('http://jwage.com');
        $url->query = 'param1=value1&param2=value2';
        $this->assertEquals(array('param1' => 'value1', 'param2' => 'value2'), $url->query->getData());
        $url->query->set('param3', 'value3');
        $this->assertEquals('param1=value1&param2=value2&param3=value3', (string) $url->query);
    }

    public function testIsAbsolute()
    {
        $url1 = new Url('http://jwage.com');
        $this->assertTrue($url1->isAbsolute());

        $url2 = new Url('/about/me');
        $this->assertFalse($url2->isAbsolute());
    }

    public function testGetResource()
    {
        $url = new Url('http://jwage.com/about?query=value');
        $this->assertEquals('/about?query=value', $url->resource);
    }
}
