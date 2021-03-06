<?php
namespace Bolt\Extension\SerWeb\Rest;

/**
 * DataFormatter library for content.
 *
 * @author Tobias Dammers <tobias@twokings.nl>
 * @author Luciano Rodriguez <info@serweb.com.ar>
 */


class DataFormatter
{
    protected $app;
    public function __construct($app)
    {
        $this->app = $app;
    }

    public function dataList($contenttype, $items)
    {

        // If we don't have any items, this can mean one of two things: either
        // the content type does not exist (in which case we'll get a non-array
        // response), or it exists, but no content has been added yet.
        if (!is_array($items)) {
            throw new \Exception("Configuration error: $contenttype is configured as a data end-point, but doesn't exist as a content type.");
        }
        if (empty($items)) {
            $items = array();
        }

        $items = array_values($items);
        $items = array_map(array($this, 'cleanListItem'), $items);

        return $items;
    }

    public function data($item)
    {
        $values = $this->cleanFullItem($item);
        return $values;
    }

    private function cleanItem($item, $type = 'list-fields')
    {
        $contenttype = $item->contenttype['slug'];
        if (isset($this->config['contenttypes'][$contenttype][$type])) {
            $fields = $this->config['contenttypes'][$contenttype][$type];
        } else {
            $fields = array_keys($item->contenttype['fields']);
        }
        // Always include the ID in the set of fields
        array_unshift($fields, 'id');
        $fields = array_unique($fields);
        $values = array();
        foreach ($fields as $key => $field) {
            $values[$field] = $item->values[$field];
        }

        // set owner
        $values['ownerid'] = $item->values['ownerid'];

        // Check if we have image or file fields present. If so, see if we need to
        // use the full URL's for these.
        if (isset($values[$key]['file'])) {
            foreach ($item->contenttype['fields'] as $key => $field) {
                if (($field['type'] == 'image' || $field['type'] == 'file') && isset($values[$key])) {
                    $values[$key]['url'] = sprintf(
                        '%s%s%s',
                        $this->app['paths']['canonical'],
                        $this->app['paths']['files'],
                        $values[$key]['file']
                    );
                }
                if ($field['type'] == 'image' && isset($values[$key]) && is_array($this->config['thumbnail'])) {
                    $values[$key]['thumbnail'] = sprintf(
                        '%s/thumbs/%sx%s/%s',
                        $this->app['paths']['canonical'],
                        $this->config['thumbnail']['width'],
                        $this->config['thumbnail']['height'],
                        $values[$key]['file']
                    );
                }
            }
        }

        $content = array(
            "values" => $values,
            "relation" => $item->relation
            );

        return $content;
    }

    private function cleanListItem($item)
    {
        return $this->cleanItem($item, 'list-fields');
    }

    private function cleanFullItem($item)
    {
        return $this->cleanItem($item, 'item-fields');
    }
}
