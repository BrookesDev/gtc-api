<?php

namespace App\Helpers;

/*
|--------------------------------------------------------------------------
| My Custom Helper Functions
|--------------------------------------------------------------------------
|
| Feel free to use anytime :)
|
*/

use App\Models\User;
use Carbon\Carbon;
use function foo\func;
use function GuzzleHttp\Psr7\build_query;
use http\Env\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Image;

if (!function_exists('api_request_response'))
{
    /**
     * Use this method to return API Responses
     *
     * @param $status
     * @param $message
     * @param $statusCode
     * @param array $data
     * @param bool $return
     * @param bool $returnArray
     * @return bool|\Illuminate\Http\JsonResponse
     */
    function api_request_response($status, $message, $statusCode, $data = [], $return = false, $returnArray = false)
    {
        $responseArray = [
            "status" => $status,
            "message" => $message,
            "data" => $data
        ];

        $response = response()->json(
            $responseArray
        );

        if ($returnArray)
        {
            return $returnArray;
        }

        if ($return) {
            return $response;
        }

        header('Content-Type: application/json', true, $statusCode);

        echo json_encode($response->getOriginalContent());

        exit();
    }
}

if (!function_exists('success_status_code'))
{
    /**
     * Returns api success code
     *
     * @return int
     */
    function success_status_code()
    {
        return 200;
    }
}

if (!function_exists('unauthorized_status_code'))
{
    function unauthorized_status_code()
    {
        return 401;
    }
}

if (!function_exists('bad_response_status_code'))
{
    /**
     * Return bad response code, 400
     *
     * @return int
     */
    function bad_response_status_code()
    {
        return 400;
    }
}

/**
 * Generete UUID
 *
 * Requires Ramsey/uuid
 * TODO: Installation => composer require ramsey/uuid
 *
 * @return string
 * @throws \Exception
 */

if (!function_exists('generate_uuid'))
{
    function generate_uuid()
    {
        return \Ramsey\Uuid\Uuid::uuid1()->toString();
    }
}

if (!function_exists('generate_access_token'))
{
    /**
     * Generate random password for user based on app name
     *
     * @param User $user
     * @return mixed
     */
    function generate_access_token(User $user)
    {
        return $user
            ->createToken(config("app.name"))
            ->accessToken;
    }
}

if (!function_exists('generate_random_password'))
{
    /**
     * @param int $length
     * @return string
     */
    function generate_random_password($length = 8)
    {
        /** @noinspection PhpDeprecationInspection */
        return str_random($length);
    }
}

if (!function_exists('get_account_period'))
{
    /**
     * @param $startDate
     * @param $endDate
     * @return \DatePeriod
     * @throws \Exception
     */
    function get_account_period($startDate, $endDate)
    {
        $start    = (new \DateTime($startDate))->modify('first day of this month');
        $end      = (new \DateTime($endDate))->modify('first day of next month');
        $interval = \DateInterval::createFromDateString('1 month');
        $period   = new \DatePeriod($start, $interval, $end);
        return $period;
    }

}

if (!function_exists('uploadFile'))
{
    /**
     * Use method to upload files to storage
     *
     * @param $request
     * @param $newName
     * @param $fileName
     * @return mixed
     */
    function uploadFile($request, $newName, $fileName)
    {
        $file = $request->file($fileName);

        $fileName = $newName.".".$file->getClientOriginalExtension();

        $fileStore = $file->storeAs('public/', $fileName);

        return Storage::url("$fileStore");
    }
}

/**
 * Requires Intervention image
 * TODO: Installation => composer require intervention/image
 * TODO: make sure to add 'Image' => Intervention\Image\ImageManagerStatic::class, instead of 'Image' => Intervention\Image\Facades\Image::class, Issues occured while using it
 *
 * Memory Limit is bumped because Intervention Image can go beyond allowed memory.
 * e.g Allowed memory size of 134217728 bytes exhausted (tried to allocate 24576 bytes) in /Users/olaoluwani/Sites/laravel/tfolc/vendor/intervention/image/src/Intervention/Image/Gd/Decoder.php on line 136
 *
 */
if (!function_exists('cropAndUploadFile'))
{
    function cropAndUploadFile($request, $newName, $fileName, $width, $height)
    {
        ini_set('memory_limit', '256M');

        $imageFile = $request->file($fileName);

        $img = Image::make($imageFile);

        $newImage = $img->fit($width, $height, function ($constraint) {
            $constraint->upsize();
        });

        $newImageFilePath = "public/$width"."x$height/".$newName.".".$imageFile->getClientOriginalExtension();

        Storage::put($newImageFilePath,(string) $newImage->encode());

        return Storage::url("$newImageFilePath");
    }
}

if (!function_exists('format_date'))
{
    /**
     * Use this method to format date
     *
     * @param $date
     * @param $newFormat
     * @return mixed
     */
    function format_date($date, $newFormat)
    {
        return Carbon::parse($date)->format($newFormat);
    }
}

if (!function_exists('delete_object'))
{
    /**
     * Delete an object
     *
     * @param $uuid
     * @param $model
     * @param $name
     * @param null $redirect
     * @return \Illuminate\Http\RedirectResponse
     */
    function delete_object($uuid, $model, $name , $redirect = null)
    {
        try {
            $object = $model::where("uuid", $uuid)->firstOrFail();
            $object->delete();

            $response = "$name deleted successfully!";

            if ($redirect != null)
            {
                return redirect()->route("$redirect")->with('success',"$response");
            } else {
                return redirect()->back()->with('success',"$response");
            }

        } catch (\Exception $exception) {
            return redirect()->back()->with('exception',$exception->getMessage());
        }
    }
}

if (!function_exists('store_or_update_object'))
{

    /**
     * Use this method to store or update data
     *
     * @param $request
     * @param $model
     * @param $data
     * @param $uuid
     * @param $objectName
     * @param null $redirect
     * @param null|array|string $fileName
     * @param null $fileNewName
     * @param array $imageResizeSize
     * @param bool $toUpdate
     * @param bool $noRedirect
     * @return array|\Illuminate\Http\RedirectResponse|string
     */
    function store_or_update_object(
        $request,
        $model,
        $data,
        $uuid,
        $objectName,
        $redirect = null,
        $fileName = null,
        $fileNewName = null,
        $imageResizeSize = [],
        $toUpdate = false,
        $noRedirect = false)
    {
        try {
            $dbData = [];

            if ($fileNewName != null)
            {
                if (is_array($fileName))
                {
                    foreach ($fileName as $filename)
                    {
                        uploadFile(
                            $request,
                            $fileNewName,
                            $filename
                        );
                    }
                } else {
                    uploadFile(
                        $request,
                        $fileNewName,
                        $fileName
                    );
                }

                if (!empty($imageResizeSize))
                {
                    if (is_array($fileName))
                    {
                        foreach ($fileName as $filename)
                        {
                            /*
                             * Task: Confirm if the specified file is an image before cropping
                             */
                            $isImage = $request->file($filename)->getClientOriginalExtension();

                            $imageExtensions = ["jpg", "jpeg", "png"];

                            if (in_array($isImage, $imageExtensions))
                            {
                                foreach ($imageResizeSize as $size)
                                {
                                    cropAndUploadFile(
                                        $request,
                                        $fileNewName,
                                        $filename,
                                        $size['width'],
                                        $size['height']
                                    );
                                }
                            }
                        }
                    } else {
                        foreach ($imageResizeSize as $size)
                        {
                            cropAndUploadFile(
                                $request,
                                $fileNewName,
                                $fileName,
                                $size['width'],
                                $size['height']
                            );
                        }
                    }
                }
            }

            if (!$toUpdate) {
                $dbData = $model::create($data);

                $response = "$objectName stored successfully!";
            } else {
                $dbData = $model::where('uuid', $uuid)->update($data);

                $response = "$objectName updated successfully!";
            }

            if (!$noRedirect)
            {
                if ($redirect != null)
                {
                    return redirect()->route("$redirect")->with('success',"$response");
                } else {
                    return redirect()->back()->with('success',"$response");
                }
            } else {
                return $dbData;
            }
        } catch (\Exception $exception) {
            if (!$noRedirect)
            {
                return redirect()->back()->withErrors(["exception" => $exception->getMessage()]);
            } else {
                return $exception->getMessage();
            }

        }
    }
}

if (!function_exists('update_object'))
{
    function update_object(
        $request,
        $model,
        $data,
        $uuid,
        $objectName,
        $redirect = null,
        $fileName = null,
        $fileNewName = null,
        $imageResizeSize = [])
    {
        try {
            if ($fileNewName != null) {

                $filePath = uploadFile(
                    $request,
                    $fileNewName,
                    $fileName
                );

                if (!empty($imageResizeSize))
                {
                    $filePath = cropAndUploadFile(
                        $request,
                        $fileNewName,
                        $fileName,
                        $imageResizeSize['width'],
                        $imageResizeSize['height']
                    );
                }
            }

            $data = $model::where('uuid', $uuid)->update($data);

            $response = "$objectName updated successfully!";

            if ($redirect != null)
            {
                return redirect()->route("$redirect")->with('success',"$response");
            } else {
                return redirect()->back()->with('success',"$response");
            }

        } catch (\Exception $exception) {
            return redirect()->back()->withErrors(["exception" => $exception->getMessage()]);
        }
    }

    if (!function_exists("generate_slug_with_uuid_suffix"))
    {
        /**
         * Use this method to generate slug with the uuid as suffix
         *
         * @param $subject
         * @param $uuid
         * @return string
         */
        function generate_slug_with_uuid_suffix($subject, $uuid)
        {
            return Str::slug($subject)."-".str_replace(["-", "-"], "", $uuid);
        }
    }
}
