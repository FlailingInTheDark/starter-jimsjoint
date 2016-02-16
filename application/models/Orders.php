<?php

/**
 * Data access wrapper for "orders" table.
 *
 * @author jim
 */
class Orders extends MY_Model {

    // constructor
    function __construct() {
        parent::__construct('orders', 'num');
    }

    /**
     * Adds an item (specified by $code) to an existing order (specified by 
     * $num).
     */
    function add_item($num, $code)
    {
        $CI = & get_instance();
        
        if ($CI->Orderitems->exists($num, $code))
        {
            $record = $CI->Orderitems->get($num, $code);
            $record->quantity++;
            $CI->Orderitems->update($record);
        }
        else
        {
            $record = $CI->Orderitems->create();
            $record->order = $num;
            $record->item = $code;
            $record->quantity = 1;
            $CI->Orderitems->add($record);
        }
    }

    /**
     * Calculates the total cost for an order.
     */
    function total($num) 
    {
        $CI = & get_instance();
        $items = $CI->Orderitems->group($num);
        $result = 0;

        if (count($items) > 0)
            foreach ($items as $item)
            {
                $menu = $CI->Menu->get($item->item);
                $result += $item->quantity * $menu->price;
            }

        return $result;
    }

    // retrieve the details for an order
    function details($num) {
        
    }

    // cancel an order
    function flush($num) {
        
    }

    // validate an order
    // it must have at least one item from each category
    function validate($num) {
        return false;
    }

}
