<?php
/**
 * Created by PhpStorm.
 * User: nathan
 * Date: 2017/2/4
 * Time: 下午12:13
 */

class BaiduLBS
{
    //API控制台申请得到的ak（此处ak值仅供验证参考使用）
    const BAIDU_APP_AK = 'QcuRDerOkzhwzLIfGYW3VUMBHjU5ViFb';//'12ZHGUnRgQH3bdD3D06ZOcqPVpTxOVvV';
    //应用类型为for server, 请求校验方式为sn校验方式时，系统会自动生成sk，可以在应用配置-设置中选择Security Key显示进行查看（此处sk值仅供验证参考使用）
    const BAIDU_APP_SK = '0ygL3bgldN2pGrXGNpHiXBNOGGS0Az2N';//'rhC28bqfuzzYtmUdMLWfxeDikwf8fV15';

    protected function caculateAKSN($sk, $url, $querystringArrays, $method = 'GET')
    {
        if ($method === 'POST'){
            ksort($querystringArrays);
        }
        $querystring = http_build_query($querystringArrays);
        return md5(urlencode($url.'?'.$querystring.$sk));
    }

    public function getLbsRgcRequestUrl($location)
    {
        $ak = self::BAIDU_APP_AK;
        $sk = self::BAIDU_APP_SK;
        //以Geocoding服务为例，地理编码的请求url，参数待填
        // $url = "http://api.map.baidu.com/geocoder/v2/?address=%s&output=%s&ak=%s&sn=%s";
        $url = "http://api.map.baidu.com/geocoder/v2/?location=%s&output=%s&ak=%s&sn=%s";
        //get请求uri前缀
        $uri = '/geocoder/v2/';
        //地理编码的请求中address参数
        //地理编码的请求output参数
        $output = 'json';
        //构造请求串数组
        $querystringArrays = array (
            'location' => $location,
            'output' => $output,
            'ak' => $ak
        );
        //调用sn计算函数，默认get请求
        $sn = $this->caculateAKSN($sk, $uri, $querystringArrays);
        //请求参数中有中文、特殊字符等需要进行urlencode，确保请求串与sn对应
        return sprintf($url, urlencode($location), $output, $ak, $sn);
    }
	
    public static function sendRequest($url)
    {    	    	
    	$ch = curl_init(); //初始化curl
    	curl_setopt($ch, CURLOPT_URL, $url);//设置链接
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置是否返回信息
    	curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    	$response = curl_exec($ch);//接收返回信息    	
    	if(curl_errno($ch)){//出错则显示错误信息
    		print curl_error($ch);
    	}
    	curl_close($ch); //关闭curl链接
    	return $response;
    }
    
    public function requestRgc($location)
    {
        if($originData = static::sendRequest($this->getLbsRgcRequestUrl($location))) {
            if($data = json_decode($originData)) {
                if($data->status == 0) {
                    return $data->result;
                } else {
                    throw new \Exception("(code:3)请求LBS API不成功,接口返回:".$originData, 3);
                }
            } else {
                throw new \Exception("(code:1)请求LBS API失败,接口返回:".$originData, 1);
            }
        } else {
            throw new \Exception("(code:2)请求LBS API失败,接口返回:".$originData, 2);
        }
    }

    //通过坐标获取地址信息
    public function getAddressByGps($location)
    {
        if($result = $this->requestRgc($location)) {
            return $result->addressComponent;
        }
    }
}