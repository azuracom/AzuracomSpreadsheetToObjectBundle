<?php

namespace Azuracom\SpreadsheetToObjectBundle\Event;

final class Events
{
    const PRE_SET_VALUES = 'azuracom.spreashet.event.pre_set_values';
    const POST_SET_VALUES = 'azuracom.spreashet.event.post_set_values';
    const PRE_SET_VALUE = 'azuracom.spreashet.event.pre_set_value';
    const POST_SET_VALUE = 'azuracom.spreashet.event.post_set_value';
    
    private function __construct(){

    }
}