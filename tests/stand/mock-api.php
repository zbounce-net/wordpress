<?php
// Test stand ONLY. Never packaged or installed on a user's WordPress.
add_filter( 'pre_http_request', function( $pre, $args, $url ) {
    if ( ( $args['headers']['X-API-Key'] ?? '' ) !== 'isolated-fixture-key-not-real' ) { return $pre; }
    if ( 'https://api.zbounce.net/v1/me' === $url ) { return [ 'response' => [ 'code' => 200 ], 'body' => wp_json_encode([ 'remaining'=>1234 ]) ]; }
    if ( 'https://api.zbounce.net/v1/fast-verify' !== $url ) { return $pre; }
    update_option( 'zbounce_test_request_count', intval(get_option('zbounce_test_request_count',0))+1 );
    $input=json_decode($args['body'],true);$email=$input['email'];
    if ( strpos($email,'failure')===0 ) {return new WP_Error('timeout','isolated fixture timeout');}
    $data=['email'=>$email,'valid'=>true,'exists'=>true,'disposable'=>false,'accept_all'=>false];
    if ( strpos($email,'rejected')===0 ) {$data['exists']=false;$data['permanent_error']=true;$data['error_category']='mailbox_not_found';}
    if ( strpos($email,'disposable')===0 ) {$data['disposable']=true;}
    if ( strpos($email,'catchall')===0 ) {$data['exists']=false;$data['accept_all']=true;}
    if ( strpos($email,'unknown')===0 ) {$data['exists']=false;$data['error_category']='temporary_error';}
    if ( strpos($email,'nomx')===0 ) {$data['exists']=false;$data['permanent_error']=true;$data['error_category']='no_mx';}
    return ['response'=>['code'=>200],'body'=>wp_json_encode($data)];
},10,3);
add_filter('pre_wp_mail',function(){return true;});
