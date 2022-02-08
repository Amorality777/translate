<?php

namespace Tests\Unit\Support\TextSplitters;

use Tests\TestCase;
use App\Support\TextSplitters\SimpleTextSplitter;

class SimpleTextSplitterTest extends TestCase
{
    private function split(string $text, int $limit = 25): array
    {
        $splitter = new SimpleTextSplitter($limit);
        $generator = $splitter->getSplitText($text);
        $result = [];
        foreach ($generator as $item) {
            $result[] = $item;
        }

        return $result;
    }

    public function testSmallText()
    {
        $text = 'Some text.';
        $result = ['Some text.'];

        $data = $this->split($text);
        $this->assertCount(1, $data);
        $this->assertEquals($data, $result);
    }

    public function testSplitOnSentences()
    {
        $text = 'Some text 1. Some text 2. Some text 3. Some text 4. Some text 5.';
        $result = ['Some text 1. Some text 2.', 'Some text 3. Some text 4.', 'Some text 5.'];

        $data = $this->split($text);
        $this->assertCount(3, $data);
        $this->assertEquals($data, $result);
    }

    public function testSplitWithEllipsis()
    {
        $text = 'Some text 1... Some text 2? Some text 3! Some text 4... Some text 5.';
        $result = ['Some text 1...', 'Some text 2? Some text 3!', 'Some text 4...', 'Some text 5.'];

        $data = $this->split($text);
        $this->assertCount(4, $data);
        $this->assertEquals($data, $result);
    }

    public function testSplitBigSentenceWithDot()
    {
        $text = 'Это очень большой текст, который имеет точку в конце.';
        $result = ['Это очень большой текст,', 'который имеет точку в', 'конце.'];

        $data = $this->split($text);
        $this->assertCount(3, $data);
        $this->assertEquals($data, $result);
    }

    public function testSplitBigSentence()
    {
        $text = 'Это ну очень большой текст, который не имеет точки в конце';
        $result = ['Это ну очень большой', 'текст, который не имеет', 'точки в конце'];

        $data = $this->split($text);
        $this->assertCount(3, $data);
        $this->assertEquals($data, $result);
    }

    public function testSplitOnSentenceAndWords()
    {
        $text = 'Это предложение должно разбиться по словам... Это короткое предложение? И это! Еще одно...';
        $result = ['Это предложение должно', 'разбиться по словам...',
            'Это короткое предложение?', 'И это! Еще одно...', ];

        $data = $this->split($text, 26);
        $this->assertCount(4, $data);
        $this->assertEquals($data, $result);
    }

    public function testChineseText()
    {
        $text = '在一个什么事都没发生的周末。我的女朋友Y小姐第42次提出和我分手。她盘着细长的小腿坐在沙发上削土豆皮。
        坏脾气的猫在撕咬她拖鞋上的绒球。';
        $result = ['在一个什么事都没发生的周末。', '我的女朋友Y小姐第42次提出和我分手。', '她盘着细长的小腿坐在沙发上削土豆皮。',
            '坏脾气的猫在撕咬她拖鞋上的绒球。', ];

        $data = $this->split($text);
        $this->assertCount(4, $data);
        $this->assertEquals($data, $result);
    }

    public function testBigChineseText()
    {
        $text = '我叫伊拉。 我是学生。 我在大学学习外语， 我会说英语和汉语。 因为我和我的朋友学习汉语， 所以， 我们要在假期去中国了解中国文化，
         练习汉语口语。 安德烈说， 我们在中国可以报语言学校， 佩佳找到了一所学校， 于是我买了飞机票。 卡佳在宾馆订了两间房间。 拿到签证以后，
          我们就出发了。 今天（二月二日， 星期一）是我们在北京的第一天。';

        $result = ['我叫伊拉。  我是学生。', '我在大学学习外语， 我会说英语和汉语。', '因为我和我的朋友学习汉语， 所以，',
            '我们要在假期去中国了解中国文化，', '练习汉语口语。', '安德烈说， 我们在中国可以报语言学校，',
            '佩佳找到了一所学校， 于是我买了飞机票。', '卡佳在宾馆订了两间房间。', '拿到签证以后，', '我们就出发了。',
            '今天（二月二日， 星期一）是我们在北京的第一天。', ];

        $data = $this->split($text);
        $this->assertCount(11, $data);
        $this->assertEquals($data, $result);
    }
}
