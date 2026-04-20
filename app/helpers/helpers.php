<?php 
function apiResponse($data=null,$status=200,$msg=''){
    return response()->json([
        'data'=> $data,
        'message'=> $msg,
    ], $status);
}
