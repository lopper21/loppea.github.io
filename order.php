<?php
if ( !isset($_POST['name']) || !isset($_POST['phone']) ){
    if (isset($_SERVER['HTTP_REFERER'])){
        header('Location: '.$_SERVER['HTTP_REFERER']);
    } else{
        header('Location: /');
    }
}

try{
    $apiConnector = new CApiConnector();

    $lead = $apiConnector->create(array(
        'name'			=> $_POST['name'],
        'phone'			=> $_POST['phone'],
        'city'			=> $_POST['city'] ?? null,
        'count'			=> $_POST['count'] ?? null,
        'offer_id'		=> '1502',
        'stream_id'		=> '',
        'country' 		=> 'PL',
        'tz' 			=> '',
        'address' 		=> '',
        'referer'		=> $_GET['referer'] ?? $_SERVER['HTTP_REFERER'] ?? null,

        'utm_source'	=> isset($_GET['utm_source'])	? $_GET['utm_source'] 	: null,
        'utm_medium'	=> isset($_GET['utm_medium'])	? $_GET['utm_medium'] 	: null,
        'utm_campaign'	=> isset($_GET['utm_campaign'])	? $_GET['utm_campaign'] : null,
        'utm_term'		=> isset($_GET['utm_term'])		? $_GET['utm_term'] 	: null,
        'utm_content'	=> isset($_GET['utm_content'])	? $_GET['utm_content'] 	: null,

        'sub_id'		=> isset($_GET['sub_id'])		? $_GET['sub_id'] 		: null,
        'sub_id_1'		=> isset($_GET['sub_id_1'])		? $_GET['sub_id_1'] 	: null,
        'sub_id_2'		=> isset($_GET['sub_id_2'])		? $_GET['sub_id_2'] 	: null,
        'sub_id_3'		=> isset($_GET['sub_id_3'])		? $_GET['sub_id_3'] 	: null,
        'sub_id_4'		=> isset($_GET['sub_id_4'])		? $_GET['sub_id_4'] 	: null,
    ));

    if( $lead ){
        header('Location: success.html?id='.$lead->id);
    }

}catch (Exception $e) {
    //error handler
    echo $e->getMessage();
}

class CApiConnector
{
    public $config = array(
        'api_key' => '2c34e06abd5f22c98e5626053c2c8b9c',
        'offer_id' => '1502',
        'user_id' => '5405',
        'api_domain' => 'http://tl-api.com',
    );

    public function create($params)
    {
        $data = array(
            'name'      => empty($params['name']) ? '' : trim($params['name']),    //name
            'phone'     => empty($params['phone']) ? '' : trim($params['phone']),   //phone
            'offer_id'  => $this->config['offer_id'],
            'country'   => empty($params['country']) ? '' : trim($params['country']), //country
        );

        $not_require_params = array(
            'tz', //Time zone
            'address', //Address
            'city', //City
            'stream_id', //Stream ID
            'count', //Count

            //utm marks
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',

            //sub-parameters
            'sub_id',
            'sub_id_1',
            'sub_id_2',
            'sub_id_3',
            'sub_id_4',

            'referer', //Referer
            'user_agent', //User Agent
            'ip', //IP
        );

        if( !empty($params) )
        {
            foreach ( $params as $param_key => $param_value )
            {
                if( in_array($param_key, $not_require_params) )
                {
                    $data[$param_key] = $param_value;
                }
            }
        }

        return $this->get_data($data, 'lead', 'create');
    }

    public function status($id)
    {
        $data = array(
            'id'  => $id,
        );

        return $this->get_data($data, 'lead', 'status');
    }

    protected function check_sum($json_data){
        return sha1($json_data . $this->config['api_key']);
    }

    protected function request($data, $model, $method, $headers = array())
    {
        $data = array(
            'user_id' => $this->config['user_id'],
            'data' => $data
        );

        $json_data = json_encode($data);

        $api_url = $this->config['api_domain'].'/api/'.$model.'/'.$method.'?'.http_build_query(array(
                'check_sum' => $this->check_sum($json_data)
            ));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if( !empty($headers) )
        {
            $http_headers = array();

            foreach( $headers as $header_name => $header_value )
            {
                $http_headers[] = $header_name.': '.$header_value;
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);
        }

        $result = curl_exec($ch);

        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close ($ch);

        $response = array(
            'error'      => $curl_error,
            'errno'      => $curl_errno,
            'http_code'  => $http_code,
            'result'     => $result,
        );

        return $response;
    }

    protected function get_data($data, $model, $method)
    {
        $response = $this->request($data, $model, $method);

        if( $response['http_code'] == 200 && $response['errno'] === 0 )
        {
            $body = json_decode($response['result']);

            if( json_last_error() === JSON_ERROR_NONE )
            {
                if( $body->status == 'ok' )
                {
                    return $body->data;
                }
                elseif( $body->status == 'error' )
                {
                    throw new Exception($body->error);
                }
                else
                {
                    throw new Exception('Unknown response status');
                }
            }
            else
            {
                throw new Exception('JSON response error');
            }
        }else{
            if( !empty($response['result']) )
            {
                $body = json_decode($response['result']);

                if( json_last_error() === JSON_ERROR_NONE )
                {
                    if( $body->status == 'error' )
                    {
                        throw new Exception($body->error);
                    }
                    else
                    {
                        throw new Exception('Unknown response status');
                    }
                }
                else
                {
                    throw new Exception('JSON response error');
                }
            }
            else
            {
                throw new Exception('HTTP request error. '.$response['error']);
            }
        }
    }
}