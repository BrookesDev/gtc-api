<?php
namespace App\Traits;
use App\Models\Pincard;
trait PincardTrait
{
    public function pincardFunction($stock_id, $user_id, $status, $start, $changes, $end, $description,$store)
    {
        $pincard = new Pincard();
        $pincard->item_id = $stock_id;
        $pincard->user_id = $user_id;
        $pincard->status = $status;
        $pincard->start = $start;
        $pincard->changes = $changes;
        $pincard->end = $end;
        $pincard->description = $description;
        $pincard->store_id = $store;
        $pincard->save();
    }
}