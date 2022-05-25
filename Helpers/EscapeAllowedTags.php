<?php

namespace MelhorEnvio\Helpers;

class EscapeAllowedTags
{

  private const TAGS_AND_ATTRIBUTES = [
    "div" => [
      "id" => [],
      "class" => [],
      "style" => []
    ],
    "input" => [
      "type" => [],
      "id" => [],
      "value" => [],
      "maxlength" => [],
      "class" => [],
      "placeholder" => [],
      "onkeyup" => [],
    ],
    "p" => [],
    "img" => [
      "src" => []
    ],
    "table" => [
      "class" => []
    ],
    "thead" => [],
    "tbody" => [],
    "small" => [
      "id" => [],
      "class" => []
    ],
    "tr" => [],
    "td" => [],
    "strong" => [],
    "style" => [],
    "form" => [],
    "a" => [
      "href" => [],
      "rel" => [],
      "target" => []
    ]
  ];

  /**
   * @param array $value
   */
  public static function allow_tags($tags)
  {

    $allowed_tags_attr = [];
    foreach ($tags as $key => $tag) {
      if (isset(self::TAGS_AND_ATTRIBUTES[$tag])) {
        $allowed_tags_attr[$tag] = self::TAGS_AND_ATTRIBUTES[$tag];
      }
    }

    return $allowed_tags_attr;
  }
}
