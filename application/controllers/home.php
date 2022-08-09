<?php

defined('DS') or exit('No direct script access.');

class Home_Controller extends Base_Controller
{
    /**
     * Handle GET /.
     *
     * @return View
     */
    public function action_index()
    {
        $authorization = Request::authorization();
        $bearer = Request::bearer();
        $content = Request::content();
        $languages = Request::languages();
        return Response::json(compact('authorization', 'bearer', 'content', 'languages'));
        // $data = [
        //     'sub' => '1234567890',
        //     'name' => 'John Doe',
        //     'iat' => 1516239022,
        // ];
        // $headers = ['alg' => 'HS256', 'typ' => 'XXX'];
        // $secret = 'secret';
        // $encoded = JWT::encode($data, $secret);
        // $decoded = JWT::decode($encoded, $secret);
        // $encrypted = RSA::encrypt('foobar');
        // $decrypted = RSA::decrypt($encrypted);
        // dd(compact('encoded', 'decoded', 'encrypted', 'decrypted'));
        return view('home.index');
    }
}
