<?php

namespace App\Http\Livewire;
use App\Models\User;
use Livewire\Component;

class Userlist extends Component
{
    public $user, $name, $email, $category_id;
    public $updateUser = false;
    public function render()
    {
        $data['users']= User::with(['roles'])->get();
        // dd('here');
        return view('livewire.userlist', $data);
    }

    public function create()
    {
        # code...
    }
}
