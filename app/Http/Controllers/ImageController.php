<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Image;

class ImageController extends Controller
{
    public function profiles($image)
    {
        $path = storage_path("app/public/profiles/$image");

        if (file_exists($path)) {

            $array = explode('.', $path);

            $type = end($array);

            $img = Image::make($path);

            return response()->make($img->encode($type))->header('Content-Type', "image/$type");

        }

        else {

            abort(404);

        }

    }

    public function receipts($image)
    {
        $path = storage_path("app/public/receipts/$image");

        if (file_exists($path)) {

            $array = explode('.', $path);

            $type = end($array);

            $img = Image::make($path);

            return response()->make($img->encode($type))->header('Content-Type', "image/$type");

        }

        else {

            abort(404);

        }

    }

}
