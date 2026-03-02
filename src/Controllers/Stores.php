<?php
namespace Simcify\Controllers;

use Simcify\Database;
use Simcify\Auth;
use Simcify\Mail;

class Stores {
    
    /**
     * Render stores
     * 
     * @return \Pecee\Http\Response
     */
    public function get() {
        
        $title = 'Stores';
        $user  = Auth::user();
        
        $stores = Database::table('stores')->where('company', $user->company)->orderBy("id", false)->get();
        foreach($stores as $key => $store){
            $store->parts = Database::table('inventory')->where('store', $store->id)->count("id", "total")[0]->total;
        }
        
        return view("stores", compact("user", "title", "stores"));
        
    }
    
    /**
     * Add a store
     * 
     * @return Json
     */
    public function create() {
        
        $user = Auth::user();
        
        $data = array(
            "company" => $user->company,
            "name" => escape(input('name'))
        );

        Database::table("stores")->insert($data);
        
        return response()->json(responder("success", "Alright!", "Store successfully added.", "reload()"));
        
    }
    
    
    /**
     * Update store view
     * 
     * @return \Pecee\Http\Response
     */
    public function updateview() {
        
        $user   = Auth::user();
        $store = Database::table('stores')->where('company', $user->company)->where('id', input("storeid"))->first();
        
        return view('modals/update-store', compact("store"));
        
    }
    
    /**
     * Update store
     * 
     * @return Json
     */
    public function update() {
        
        $user = Auth::user();
        
        $data = array(
            "name" => escape(input('name'))
        );

        Database::table('stores')->where('id', input('storeid'))->update($data);
        return response()->json(responder("success", "Alright!", "Store successfully updated.", "reload()"));
        
    }
    
    /**
     * Delete store
     * 
     * @return Json
     */
    public function delete() {
        
        $user = Auth::user();
        
        $parts = Database::table('inventory')->where('store', input('storeid'))->count("id", "total")[0]->total;
        if(!empty($parts)){
            return response()->json(responder("warning", "Hmmm!", "Some parts inside the store, move to another store before deleting."));
        }
        Database::table('stores')->where('id', input('storeid'))->where('company', $user->company)->delete();
        
        return response()->json(responder("success", "Alright!", "store successfully deleted.", "reload()"));
        
    }
    
    
}