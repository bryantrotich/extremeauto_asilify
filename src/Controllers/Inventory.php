<?php
namespace Simcify\Controllers;

use Simcify\Database;
use Simcify\Auth;

class Inventory {
    
    /**
     * Render Inventory page
     * 
     * @return \Pecee\Http\Response
     */
    public function get() {
        
        $title = 'Inventory';
        $user  = Auth::user();
        $page  = 1;
        $limit = 10;        
        
        if ($user->role == "Staff" || $user->role == "Booking Manager") {
            return view('errors/404');
        }

        if( isset($_GET['page']) ){
            $page  = $_GET['page'];
        }

        if( isset($_GET['limit']) ){
            $limit = $_GET['limit'];
        }        
        
        $inventory = Database::table('inventory')->where('company', $user->company)->where('received', "Yes")->where('project_specific', "No")->orWhere('company', $user->company)->where('received', "Yes")->where('project_specific', "Yes")->where("quantity",">" ,"0")->orderBy("id", false)->paginate($page,$limit);
        
        foreach ($inventory->results as $key => $item) {
            if(!empty($item->supplier)){
                $item->supplier = Database::table('suppliers')->where('company', $user->company)->where('id', $item->supplier)->first();
                $item->make = Database::table('makes')->where('id', $item->make)->first();
                $item->model = Database::table('models')->where('id', $item->model)->first();
            }
        }

        $suppliers = Database::table('suppliers')->where('company', $user->company)->orderBy("id", false)->get();
        $stores = Database::table('stores')->where('company', $user->company)->orderBy("id", false)->get();
        
        $makes = Database::table('makes')->get();
        
        return view("inventory", compact("user", "title", "inventory","suppliers","stores","makes"));
        
    }

    /**
     * Models of a make
     * 
     * @return Json
     */
    public function models() {

        $sortedmodels = array(array(
                "id" => "",
                "text" => "Select Model"
            ));

        if (empty(input("makeid"))) {
            return response()->json($sortedmodels);
        }

        $models = Database::table('models')->where('makeid', input("makeid"))->orderBy("id", false)->get();
        foreach ($models as $key => $model) {
            $sortedmodels[] = array(
                "id" => $model->id,
                "text" => $model->name
            );
        }

        return response()->json($sortedmodels);

    }    
    
    /**
     * Render receiveables page
     * 
     * @return \Pecee\Http\Response
     */
    public function receiveables() {
        
        $title = 'Inventory';
        $user  = Auth::user();
        
        if ($user->role == "Staff" || $user->role == "Booking Manager") {
            return view('errors/404');
        }
        
        $inventory = Database::table('inventory')->where('company', $user->company)->where('received', "No")->orderBy("id", false)->get();
        foreach ($inventory as $key => $item) {
            $item->project = Database::table('projects')->where('id', $item->project)->first();
            if(!empty($item->supplier)){
                $item->supplier = Database::table('suppliers')->where('company', $user->company)->where('id', $item->supplier)->first();
            }
        }
        
        return view("receiveables", compact("user", "title", "inventory"));
        
    }
    
    /**
     * Render issueables page
     * 
     * @return \Pecee\Http\Response
     */
    public function issueables() {
        
        $title = 'Inventory issueables';
        $user  = Auth::user();
        
        if ($user->role == "Staff" || $user->role == "Booking Manager") {
            return view('errors/404');
        }
        
        $inventory = Database::table('inventory')->where('company', $user->company)->where("project_specific" ,"Yes")->where("received" ,"Yes")->where("quantity",">" ,"0")->orderBy("id", false)->get();
        foreach ($inventory as $key => $item) {
            $item->project = Database::table('projects')->where('id', $item->project)->first();
            if(!empty($item->supplier)){
                $item->supplier = Database::table('suppliers')->where('company', $user->company)->where('id', $item->supplier)->first();
            }
        }
        
        return view("issueables", compact("user", "title", "inventory"));
        
    }
    
    /**
     * Update inventory
     * 
     * @return Json
     */
    public function confirmreceipt() {
        
        $user = Auth::user();

        Database::table('inventory')->where('id', input('inventoryid'))->where('company', $user->company)->update(array(
            "received" => "Yes"
        ));

        $inventory = Database::table('inventory')->where('company', $user->company)->where('id', input("inventoryid"))->first();
        if (!empty($inventory->expense)) {
            Database::table('expenses')->where('id', $inventory->expense)->where('company', $user->company)->update(array(
                "status" => "Delivered"
            ));
        }
        
        return response()->json(responder("success", "Alright!", "Item receipt confirmed.", "reload()"));
        
    }
    
    /**
     * Create inventory 
     * 
     * @return Json
     */
    public function create() {
        
        $user = Auth::user();
        
        $data = array(
            "company" => $user->company,
            "name" => escape(input('name')),
            "store" => escape(input('store')),
            "quantity" => escape(input('quantity')),
            "quantity_unit" => escape(input('quantity_unit')),
            "shelf_number" => escape(input('shelf_number')),
            "item_code" => escape(input('item_code')),
            "unit_cost" => escape(input('unit_cost')),
            "make" => escape(input('make')),
            "model" => escape(input('model')),
            "part_number" => escape(input('part_number')),
        );
        if (!empty(input("supplier"))) {
            $data["supplier"] = escape(input("supplier"));
        }

        if (!empty(input("restock_quantity"))) {
            $data["restock_quantity"] = escape(input("restock_quantity"));
        }else{
            $data["restock_quantity"] = 0.00;
        }

        Database::table('inventory')->insert($data);
        
        return response()->json(responder("success", "Alright!", "Item successfully added.", "reload()"));
        
    }
    
    
    /**
     * inventory update view
     * 
     * @return \Pecee\Http\Response
     */
    public function updateview() {
        
        $user      = Auth::user();
        $inventory = Database::table('inventory')->where('company', $user->company)->where('id', input("inventoryid"))->first();
        $suppliers = Database::table('suppliers')->where('company', $user->company)->orderBy("id", false)->get();
        $stores    = Database::table('stores')->where('company', $user->company)->orderBy("id", false)->get();
        $makes     = Database::table('makes')->get();
        $models    = Database::table('models')->where('makeid',$inventory->make)->get();
        
        return view('modals/update-inventory', compact("inventory","makes","models","suppliers","user","stores"));
        
    }
    
    /**
     * Update inventory
     * 
     * @return Json
     */
    public function update() {
        
        $user = Auth::user();
        
        $data = array(
            "name" => escape(input('name')),
            "quantity" => escape(input('quantity')),
            "store" => escape(input('store')),
            "quantity_unit" => escape(input('quantity_unit')),
            "shelf_number" => escape(input('shelf_number')),
            "item_code" => escape(input('item_code')),
            "unit_cost" => escape(input('unit_cost')),
            "make" => escape(input('make')),
            "model" => escape(input('model')),
            "part_number" => escape(input('part_number')),            
        );

        if (!empty(input("supplier"))) {
            $data["supplier"] = escape(input("supplier"));
        }

        if (!empty(input("restock_quantity"))) {
            $data["restock_quantity"] = escape(input("restock_quantity"));
        }else{
            $data["restock_quantity"] = 0.00;
        }

        Database::table('inventory')->where('id', input('inventoryid'))->where('company', $user->company)->update($data);
        return response()->json(responder("success", "Alright!", "Item successfully updated.", "reload()"));
        
    }
    
    /**
     * add stock
     * 
     * @return Json
     */
    public function addstock() {
        
        $user = Auth::user();

        $inventory = Database::table('inventory')->where('company', $user->company)->where('id', input("inventoryid"))->first();
        $quantity = $inventory->quantity + input("quantity");
        
        $data = array(
            "quantity" => escape($quantity)
        );

        Database::table('inventory')->where('id', input('inventoryid'))->where('company', $user->company)->update($data);
        return response()->json(responder("success", "Alright!", "Stock successfully updated.", "reload()"));
        
    }
    
    
    /**
     * inventory issue view
     * 
     * @return \Pecee\Http\Response
     */
    public function issueview() {
        
        $user   = Auth::user();
        $inventory = Database::table('inventory')->where('company', $user->company)->where('id', input("inventoryid"))->first();

        if (empty($inventory->project)) {
            $projects = Database::table('projects')->where('company', $user->company)->where('status', "In Progress")->orWhere('company', $user->company)->where('status', "Booked In")->orderBy("id", false)->get();
            $project = NULL;
        }else{
            $projects = NULL;
            $project = Database::table('projects')->where('id', $inventory->project)->first();
        }
        $staffmembers = Database::table('users')->where('company', $user->company)->orderBy("id", false)->get();
        
        return view('modals/issue-inventory', compact("inventory","projects","user","project","staffmembers"));
        
    }
    
    /**
     * Update inventory
     * 
     * @return Json
     */
    public function issue() {
        
        $user = Auth::user();

        if (input("consumed") < 0) {
            return response()->json(responder("error", "Hmmm!", "Units Consumed can't be less than 0."));
        }
        $inventory = Database::table('inventory')->where('company', $user->company)->where('id', input("inventoryid"))->first();
        
        $data = array(
            "company" => $user->company,
            "project" => escape(input('project')),
            "item" => escape(input('inventoryid')),
            "issued_to" => escape(input('issued_to')),
            "issued_by" => escape($user->fname." ".$user->lname),
            "consumed" => escape(input('consumed')),
            "issued_on" => escape(input('issued_on')),
            "consumed_value" => escape(input('consumed') * $inventory->unit_cost)
        );

        Database::table('inventory')->where('company', $user->company)->where('id', input("inventoryid"))->update(array(
            "quantity" => round(($inventory->quantity - input('consumed')), 2)
        ));

        Database::table('inventorylog')->insert($data);

        if($inventory->project_specific == "No"){
            self::addexpense($user, ( object ) $data, $inventory);
        }

        return response()->json(responder("success", "Alright!", "Item successfully issued.", "reload()"));
        
    }
    
    /**
     * Add issued item to parts & expenses
     * 
     * @return Json
     */
    public function addexpense($user, $payload, $inventory) {

        $data = array(
            "company" => $user->company,
            "project" => $payload->project,
            "inventory" => $inventory->id,
            "expense" => $inventory->name,
            "amount" => $payload->consumed_value,
            "quantity" => $payload->consumed,
            "units" => $inventory->quantity_unit,
            "expense_date" => $payload->issued_on
        );
        
        Database::table('expenses')->insert($data);

    }
    
    /**
     * Delete inventory
     * 
     * @return Json
     */
    public function delete() {
        
        $user = Auth::user();
        Database::table('inventory')->where('id', input('inventoryid'))->where('company', $user->company)->delete();
        Database::table('notes')->where('item', input('inventoryid'))->where('type', "Inventory")->where('company', $user->company)->delete();
        
        return response()->json(responder("success", "Alright!", "Item successfully deleted.", "redirect('" . url("Inventory@get") . "', true)"));
        
    }
    
}