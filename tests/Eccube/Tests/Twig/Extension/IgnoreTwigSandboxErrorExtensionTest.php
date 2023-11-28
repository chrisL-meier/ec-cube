<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Tests\Twig\Extension;

use Eccube\Entity\Page;
use Eccube\Tests\Web\AbstractWebTestCase;

class IgnoreTwigSandboxErrorExtensionTest extends AbstractWebTestCase
{
    /**
     * @dataProvider twigSnippetsProvider
     * @dataProvider twigVarFreeAreaProvider
     */
    public function testFreeArea($snippet, $whitelisted)
    {
        $Product = $this->createProduct();
        $Product->setFreeArea('__RENDERED__'.$snippet);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateUrl('product_detail', ['id' => $Product->getId()]));
        $text = $crawler->text();

        // $snippetがsandboxで制限された場合はフリーエリアは空で出力されるため、__RENDERED__の出力有無で結果を確認する
        self::assertStringContainsString($whitelisted ? '__RENDERED__' : '', $text);
    }

    /**
     * @dataProvider twigSnippetsProvider
     * @dataProvider twigVarMetaTagsProvider
     */
    public function testMetatags($snippet, $whitelisted)
    {
        $Page = $this->entityManager->getRepository(Page::class)->find(1);
        $Page->setMetaTags('__RENDERED__'.$snippet);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateUrl($Page->getUrl()));
        $text = $crawler->text();

        // ホワイトリストに入っている場合__RENDERED__が表示される
        if ($whitelisted) {
            self::assertStringContainsString('__RENDERED__', $text);
        } else {
            self::assertStringNotContainsString('__RENDERED__', $text);
        }
        // 入力可能ではない値の場合は、システムエラーが発生する
        self::assertStringNotContainsString('システムエラーが発生しました', $text);

    }

    public function twigSnippetsProvider()
    {
        // 0: twigスニペット, 1: ホワイトリスト対象かどうか
        return [
            ['{% set foo = "bar" %}', true],
            ['{% spaceless %}<div> <strong>test</strong> </div>{% endspaceless %}', true],
            ['{% flush %}', true],
            ['{% apply lower|escape("html") %}<strong>SOME TEXT</strong>{% endapply %}', true],
            ['{{ "-5"|abs }}', true],
            ['{{ "2020/02/01"|date_modify("+1 day")|date("m/d/Y") }}', true],
            ['{{ [1, 2, 3, 4]|first }}', true],
            ['{{ url("homepage") }}', true],
            ['{{ random(1, 100) }}', true],
            ['{% for i in range(3, 0) %} {{ i }}, {% endfor %}', true],
        ];
    }

    public function twigVarFreeAreaProvider()
    {
        // 0: twigスニペット, 1: ホワイトリスト対象かどうか
        return [
            ['{{ Product.name }}', true],
            ['{{ app.request.uri }}', true],
            ['{{ app.request.getUri }}', true],
        ];
    }

    public function twigVarMetaTagsProvider()
    {
        // 0: twigスニペット, 1: ホワイトリスト対象かどうか
        return [
            ['{{ BaseInfo.shop_name }}', true],
            ['{{ app.request.uri }}', true],
            ['{{ app.request.getUri }}', true],
        ];
    }
}
