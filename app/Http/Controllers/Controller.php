<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class Controller.
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    // TODO PUT THIS WORKING ON THE FUTURE
    // /**
    //  * @param $result
    //  * @param $message
    //  * @return mixed
    //  */
    // public function sendResponse($result, $message)
    // {
    //     return Response::json(ResponseUtil::makeResponse($message, $result));
    // }

    // /**
    //  * @param $error
    //  * @param int $code
    //  * @return \Illuminate\Http\JsonResponse
    //  */
    // public function sendError($error, $code = 404)
    // {
    //     return Response::json(ResponseUtil::makeError($error), $code);
    // }

}
