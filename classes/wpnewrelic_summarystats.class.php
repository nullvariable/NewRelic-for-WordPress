<?php
class wpnewrelic_summarystats {
    function __construct($xml) {
        $parsedxml = simplexml_load_string($xml);
        foreach ($parsedxml->threshold_value as $value) {
            $attrs = $this->clean_array($value->attributes());
            $name = (string)$attrs['name'];
            $this->items->{$name} = $attrs;
        }
        //d(get_defined_vars());
    }
    function clean_array($attrs) {
        $return = array();
        foreach ($attrs as $key => $value) {
            $return[$key] = (string)$value;
        }
        return $return;
    }
    /*function __toString() {
        $string = '';
        foreach ($this->items as $item) {
            $string .= $item['name'].' '.$item['formatted_metric_value'];
        }
        return $string;
    }*/
}