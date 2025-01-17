<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * CampaignXmlFeedParser
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3
 */

class CampaignXmlFeedParser
{
    public static $maxItemsCount = 100;

    public static $itemsCount = 10;

    public static function parseContent($content, Campaign $campaign, ListSubscriber $subscriber = null, $cache = false, $cacheKeySuffix = null)
    {
        if (!$cacheKeySuffix) {
            $cacheKeySuffix = $content;
        }
        $cacheKey = sha1(__METHOD__ . $campaign->campaign_uid . sha1($cacheKeySuffix));
        if ($cache && ($cachedContent = Yii::app()->cache->get($cacheKey))) {
            return $cachedContent;
        }

        $content = StringHelper::decodeSurroundingTags($content);
        if (strpos($content, '[XML_FEED_BEGIN ') === false || strpos($content, '[XML_FEED_END]') === false) {
            return $content;
        }

        if (!preg_match_all('/\[XML_FEED_BEGIN(.*?)\]((?!\[XML_FEED_END).)*\[XML_FEED_END\]/sx', $content, $multiMatches)) {
            return $content;
        }

        if (!isset($multiMatches[0], $multiMatches[0][0])) {
            return $content;
        }

        foreach ($multiMatches[0] as $fullFeedHtml) {
            $_fullFeedHtml = CHtml::decode($fullFeedHtml);
            $matchKeyBefore= sha1($_fullFeedHtml);
            $searchReplace = CampaignHelper::getCommonTagsSearchReplace($_fullFeedHtml, $campaign, $subscriber);
            $_fullFeedHtml = str_replace(array_keys($searchReplace), array_values($searchReplace), $_fullFeedHtml);
            $_fullFeedHtml = CampaignHelper::getTagFilter()->apply($_fullFeedHtml, $searchReplace);
            $matchKeyAfter = sha1($_fullFeedHtml);

            if (!preg_match('/\[XML_FEED_BEGIN(.*?)\](.*)\[XML_FEED_END\]/sx', $_fullFeedHtml, $matches)) {
                continue;
            }

            if (!isset($matches[0], $matches[2])) {
                continue;
            }

            $feedItemTemplate = $matches[2];

            preg_match('/\[XML_FEED_BEGIN(.*?)\]/', $_fullFeedHtml, $matches);
            if (empty($matches[1])) {
                continue;
            }

            $attributesPattern  = '/(\w+) *= *(?:([\'"])(.*?)\\2|([^ "\'>]+))/';
            preg_match_all($attributesPattern, $matches[1], $matches, PREG_SET_ORDER);
            if (empty($matches)) {
                continue;
            }

            $attributes = array();
            foreach ($matches as $match) {
                if (!isset($match[1], $match[3])) {
                    continue;
                }
                $attributes[strtolower($match[1])] = $match[3];
            }

            $attributes['url'] = isset($attributes['url']) ? str_replace('&amp;', '&', $attributes['url']) : null;
            if (!$attributes['url'] || !FilterVarHelper::url($attributes['url'])) {
                continue;
            }

            $count = self::$itemsCount;
            if (isset($attributes['count']) && (int)$attributes['count'] > 0 && (int)$attributes['count'] <= self::$maxItemsCount) {
                $count = (int)$attributes['count'];
            }

            $doCache   = $matchKeyBefore == $matchKeyAfter && !$campaign->isDraft && $cache;
            $feedItems = self::getRemoteFeedItems($attributes['url'], $count, $campaign, $doCache);

            if (empty($feedItems)) {
                continue;
            }

            $feedItemsMap = array(
                '[XML_FEED_ITEM_TITLE]'         => 'title',
                '[XML_FEED_ITEM_DESCRIPTION]'   => 'description',
                '[XML_FEED_ITEM_CONTENT]'       => 'content',
                '[XML_FEED_ITEM_IMAGE]'         => 'image',
                '[XML_FEED_ITEM_LINK]'          => 'link',
                '[XML_FEED_ITEM_PUBDATE]'       => 'pubDate',
                '[XML_FEED_ITEM_GUID]'          => 'guid',
            );

            $html = '';
            foreach ($feedItems as $feedItem) {
                $itemHtml = $feedItemTemplate;
                foreach ($feedItemsMap as $tag => $mapValue) {
                    if (!isset($feedItem[$mapValue]) || !is_string($feedItem[$mapValue])) {
                        continue;
                    }
                    $itemHtml = str_replace($tag, $feedItem[$mapValue], $itemHtml);
                }
                if (sha1($itemHtml) != sha1($feedItemTemplate)) {
                    $html .= $itemHtml;
                }
            }

            $content = str_replace($fullFeedHtml, $html, $content);
        }

        if ($doCache) {
            Yii::app()->cache->set($cacheKey, $content, MW_CACHE_TTL);
        }

        return $content;
    }

    public static function getRemoteFeedItems($url, $count = 10, Campaign $campaign, $cache = false)
    {
        $cacheKey = sha1(__METHOD__ . $campaign->campaign_uid . $url . $count);
        if ($cache && ($items = Yii::app()->cache->get($cacheKey))) {
            return $items;
        }

        $useErrors = libxml_use_internal_errors(true);
        $items     = array();
        $xml       = simplexml_load_file($url, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (empty($xml)) {
            libxml_clear_errors();
            libxml_use_internal_errors($useErrors);
            return $items;
        }

        $namespaces = $xml->getNamespaces(true);

        if (empty($xml->channel) || empty($xml->channel->item)) {
            libxml_clear_errors();
            libxml_use_internal_errors($useErrors);
            return $items;
        }

        foreach($xml->channel->item as $item) {

            if (count($items) >= $count) {
                break;
            }

            $itemMap = array(
                'title'         => null,
                'description'   => null,
                'content'       => null,
                'image'         => null,
                'link'          => null,
                'pubDate'       => null,
                'guid'          => null,
            );

            if (!empty($item->title)) {
                $itemMap['title'] = (string)$item->title;
            }

            if (!empty($item->description)) {
                $itemMap['description'] = (string)$item->description;
            }

            $content = $item->children('content', true);
            if (!empty($content->encoded)) {
                $itemMap['content'] = (string)$content->encoded;
            }

            if (!empty($namespaces['media'])) {
                $media = $item->children($namespaces['media']);
                if (!empty($media) && !empty($media->content)) {
                    $itemMap['image'] = (string)$media->content;
                }
            }

            if (empty($itemMap['image']) && !empty($item->enclosure) && !empty($item->enclosure->url) && !empty($item->enclosure->type) && strpos((string)$item->enclosure->type, 'image/') !== false ) {
                $itemMap['image'] = (string)$item->enclosure->url;
            }

            if (!empty($item->link)) {
                $itemMap['link'] = (string)$item->link;
            }

            if (!empty($item->pubDate)) {
                $itemMap['pubDate'] = (string)$item->pubDate;
            }

            if (!empty($item->guid)) {
                $itemMap['guid'] = (string)$item->guid;
            }

            $itemMap = array_map(array('CHtml', 'decode'), $itemMap);
            // $itemMap = array_map(array('CHtml', 'encode'), $itemMap);
            $items[] = $itemMap;
        }

        libxml_clear_errors();
        libxml_use_internal_errors($useErrors);

        if ($cache) {
            Yii::app()->cache->set($cacheKey, $items, MW_CACHE_TTL);
        }

        return $items;
    }
}
