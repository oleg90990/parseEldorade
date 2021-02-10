<?php

require "vendor/autoload.php";
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\EmptyCollectionException;

function getBetween($content, $start, $end)
{
    $n = explode($start, $content);
    $result = array();
    foreach ($n as $val) {
        $pos = strpos($val, $end);
        if ($pos !== false) {
            $result[] = substr($val, 0, $pos);
        }
    }
    return $result;
}

function parseCats($url)
{
    $dom = (new Dom)
      ->loadFromUrl($url);

    foreach ($dom->find('._2lU4V3p') as $cat) {
        parseCat('https://www.eldorado.ru' . $cat->getAttribute('href'));
    }
}

function parseCat($url)
{
    $dom = (new Dom)
      ->loadFromUrl($url);

    $maxPages = 0;

    foreach ($dom->find('.qkT05Iu') as $page) {
        $prevli = count($page->find('li')) - 2;
        $maxPages = (int) $page->find('li')[$prevli]->find('a')->text;
    }

    for ($i = 1; $i <= $maxPages; $i++) {
        parsePage($url . '?page=' . $i);
    }
}

function parsePage($url)
{
    consoleLog("парсим: $url");
    try {
        $dom = new Dom;
        $dom->loadFromUrl($url);

        $find = 0;

        foreach ($dom->find('[data-dy="product"]') as $product) {
            $src = $product->find('._1RaAPF1 img')[0]->getAttribute('src');
            $href = 'https://www.eldorado.ru' . $product->find('._1RaAPF1')->getAttribute('href');
            $name = str_replace('&quot;', '', $product->find('._32Sm557')->text);
            $article = $product->find('[data-dy="article"]')->text;
            $price = $product->find('[data-pc="offer_price"]')->text;
            $price = intval(str_replace(' ', '', $price));
            $inStock = count($product->find('._2RKM8nz span')) === 0;

            $sale = false;
            foreach ($product->find('[data-dy="prm-pormocodes"]') as $pormocode) {
                $sale = $pormocode->find('img')[0]->getAttribute('alt');
                $sale = getBetween($sale, 'Скидка ', '% по промокоду')[0] ?? false;
            }

            if ($sale && $inStock) {
                $find++;
                file_put_contents('parse.csv', "$name,$href,$price,$sale,$article" . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        }
    } catch (EmptyCollectionException $e) {
      writeError($e->getMessage());
    }
    consoleLog("записано: $find продуктов");
}

function consoleLog($text) {
  echo $text . PHP_EOL;
}

function writeCsv($text) {
  file_put_contents('parse.csv', $text . PHP_EOL);
}

function writeError($text) {
  file_put_contents('error.txt', $text . PHP_EOL);
}

$pages = [
  'https://www.eldorado.ru/d/tekhnika-dlya-kukhni/',
  'https://www.eldorado.ru/d/smartfony-mobilnye-telefony/',
  'https://www.eldorado.ru/d/tekhnika-dlya-doma/',
  'https://www.eldorado.ru/c/televizory/'
];

writeCsv('Наименование,Ссылка,Цена,Скидка,Акртикул');

foreach ($pages as $page) {
  parseCats($page);
}