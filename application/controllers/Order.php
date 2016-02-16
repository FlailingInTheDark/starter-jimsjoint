<?php

/**
 * Order handler
 * 
 * Implement the different order handling usecases.
 * 
 * controllers/welcome.php
 *
 * ------------------------------------------------------------------------
 */
class Order extends Application 
{

    function __construct() 
    {
        parent::__construct();
    }

    // start a new order
    function neworder() 
    {
        $order_num = $this->Orders->highest() + 1;
        $new_order = $this->Orders->create();
        $new_order->num = $order_num;
        $new_order->date = date();
        $new_order->status = 'a';
        $new_order->total = 0;
        $this->Orders->add($new_order);
        redirect('/order/display_menu/' . $order_num);
        
    }

    // add to an order
    function display_menu($order_num = null) 
    {
        if ($order_num == null)
            redirect('/order/neworder');

        $this->data['pagebody'] = 'show_menu';
        $this->data['order_num'] = $order_num;
        $this->data['title'] = 'Preparing Order #' . $order_num . '  -  Cost: $' 
                . number_format($this->Orders->total($order_num), 2);
        
        // Make the columns
        $this->data['meals'] = $this->make_column('m');
        $this->data['drinks'] = $this->make_column('d');
        $this->data['sweets'] = $this->make_column('s');

        // Bit of a hokey patch here, to work around the problem of the template
        // parser no longer allowing access to a parent variable inside a
        // child loop - used for the columns in the menu display.
        // this feature, formerly in CI2.2, was removed in CI3 because
        // it presented a security vulnerability.
        // 
        // This means that we cannot reference order_num inside of any of the
        // variable pair loops in our view, but must instead make sure
        // that any such substitutions we wish make are injected into the 
        // variable parameters
        // Merge this fix into your origin/master for the lab!
        $this->hokeyfix($this->data['meals'], $order_num);
        $this->hokeyfix($this->data['drinks'], $order_num);
        $this->hokeyfix($this->data['sweets'], $order_num);
        // end of hokey patch

        $this->render();
    }

    // inject order # into nested variable pair parameters
    function hokeyfix($varpair, $order) 
    {
        foreach ($varpair as &$record)
            $record->order_num = $order;
    }

    // make a menu ordering column
    function make_column($category) 
    {
        return $this->Menu->some('category', $category);
    }

    // add an item to an order
    function add($order_num, $item) 
    {
        $this->Orders->add_item($order_num, $item);
        redirect('/order/display_menu/' . $order_num);
    }

    // checkout 
    function checkout($order_num) 
    {
        $this->data['title'] = 'Checking Out';
        $this->data['pagebody'] = 'show_order';
        $this->data['order_num'] = $order_num;
        $this->data['total'] = number_format($this->Orders->total($order_num), 2);
        $items = $this->Orderitems->group($order_num);
        foreach ($items as $item)
        {
            $menu_item = $this->Menu->get($item->item);
            $item->code = $menu_item->name;
        }
        $this->data['items'] = $items;
        
        if ($this->Orders->validate($order_num))
        {
          $this->data['okornot'] =  '';
        }
        else
        {
           $this->data['okornot'] =  'disabled'; 
        }
        
        $this->render();
    }

    // proceed with checkout
    function commit($order_num) 
    {
        if(!$this->Orders->validate($order_num))
        {
            redirect('/order/display_menu/' . $order_num);
        }
        $record = $this->Orders->get($order_num);
        $record->date = date(DATE_ATOM);
        $record->status = 'c';
        $record->total = $this->Orders->total($order_num);
        $this->Orders->update($record);
        redirect('/');
    }

    // cancel the order
    function cancel($order_num) 
    {
        $this->Orderitems->delete_some($order_num);
        $record = $this->Orders->get($order_num);
        $record->status = 'x';
        $this->Orders->update($record);

        redirect('/');
    }

}
