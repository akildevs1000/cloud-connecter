<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CommonController extends Controller
{

    public function destroy($token, $file)
    {
        try {
            if (Hash::check($token, env("APP_TOKEN"))) {
                if (file_exists(base_path($file))) {
                    unlink(base_path($file));
                    return "File reseted";
                }
            }
            return "File can`t delete";
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}