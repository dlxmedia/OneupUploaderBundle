<?php

declare(strict_types=1);

namespace Oneup\UploaderBundle\Tests\Templating;

use Oneup\UploaderBundle\Templating\Helper\UploaderHelper;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TemplateHelperTest extends WebTestCase
{
    public function testName(): void
    {
        self::ensureKernelShutdown();

        $client = static::createClient();

        /** @var ContainerInterface $container */
        $container = $client->getContainer();

        /** @var UploaderHelper $helper */
        $helper = $container->get('oneup_uploader.templating.uploader_helper');

        // this is for code coverage
        $this->assertSame($helper->getName(), 'oneup_uploader');
    }

    public function testNonExistentMappingForMaxSize(): void
    {
        $this->expectException('\InvalidArgumentException');

        self::ensureKernelShutdown();

        $client = static::createClient();

        /** @var ContainerInterface $container */
        $container = $client->getContainer();

        /** @var UploaderHelper $helper */
        $helper = $container->get('oneup_uploader.templating.uploader_helper');
        $helper->maxSize(uniqid());

        $this->fail('No exception has been raised');
    }
}
