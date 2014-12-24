<?php 

/*
* Author By Peanode
* https://github.com/peanode/12306
*/

    require_once('PHPMailer-5.2.9/PHPMailerAutoload.php');

/**
 * Class Huijia
 */
class Huijia{
        public $date_arr;
        public $train_arr;
        public $type_arr;
        public $url_arr;
        public $start_station;
        public $end_station;

    /**
     * @param $date_arr
     * @param $start_station
     * @param $end_station
     * @param $train_arr
     * @param $type_arr
     */
    public function __construct($date_arr, $start_station, $end_station, $train_arr, $type_arr){
            $this->setDateArr($date_arr);
            $this->setStartStation($start_station);
            $this->setEndStation($end_station);
            $this->setTrainArr($train_arr);
            $this->setTypeArr($type_arr);
            $this->setUrlArr();
        }

    /**
     * @param Array $data_arr
     */
    public function setDateArr($date_arr)
    {
        if(empty($date_arr) || !is_array($date_arr)){
            exit('Date is NULL');
        }
        $this->date_arr = $date_arr;
    }

    /**
     * @param Array $start_station
     */
    public function setStartStation($start_station)
    {
        if(empty($start_station) || !is_array($start_station)){
            exit('START station is NULL');
        }
        $this->start_station = $start_station;
    }

    /**
     * @param mixed $end_station
     */
    public function setEndStation($end_station)
    {
        if(empty($end_station) || !is_array($end_station)){
            exit('END station is NULL');
        }
        $this->end_station = $end_station;
    }

    /**
     * @param Array $train_arr
     */
    public function setTrainArr($train_arr)
    {
        $this->train_arr = $train_arr;
    }

    /**
     * @param Array $type_arr
     */
    public function setTypeArr($type_arr)
    {
        if(empty($type_arr) || !is_array($type_arr)){
            exit('Type is NULL');
        }
        $this->type_arr = $type_arr;
    }

    /**
     * @param Array $url_arr
     */
    public function setUrlArr()
    {
        //https://kyfw.12306.cn/otn/lcxxcx/query?purpose_codes=ADULT&queryDate=2015-02-15&from_station=HZH&to_station=WHN
        foreach($this->start_station as $start){
            foreach($this->end_station as $end){
                foreach($this->date_arr as $date){
                    $this->url_arr[] = 'https://kyfw.12306.cn/otn/lcxxcx/query?purpose_codes=ADULT&queryDate='.$date.'&from_station='.$start.'&to_station='.$end;
                }
            }
        }
    }

    /**
     * @param $url
     * @return string
     */
    public function getSslPage($url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_REFERER, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        }

    /**
     * @param $json
     * @return array
     */
    public function checkAvailable($json) {
            $data_arr = json_decode($json, TRUE);
            $return = array();
            if(!isset($data_arr['data']['datas'])){
                $return['available'] = 0;
                return $return;
            }
            foreach($data_arr['data']['datas'] as $train){
                if(empty($this->train_arr) || in_array($train['station_train_code'], $this->train_arr)){
                    if ($train['canWebBuy'] == 'N') {
                        continue;
                    }
                    foreach($this->type_arr as $type=>$flag){
                        if($flag == 1){
                            if($train[$type] > 0){
                                $return['data'][$train['start_train_date']][$train['station_train_code']] = $train;
                            }
                        }
                    }
                }else{
                    continue;
                }
            }
            if(empty($return)){
                $return['available'] = 0;
            }else{
                $return['available'] = 1;
            }
            return $return;
        }

    /**
     * @return bool|string
     */
    public  function checkTicket(){
            if(!is_array($this->url_arr) || empty($this->url_arr)){
                exit('URL list is NULL');
            }
            $result = array();
            foreach($this->url_arr as $url){
                $json_str = $this->getSslPage($url);
                $return = $this->checkAvailable($json_str);
                if($return['available'] == 1){
                    $result[] = $return['data'];
                }else{
                    continue;
                }
            }
            if(!empty($result)){
                return $this->getHtmlContent($result);
            }else{
                return false;
            }
        }

    /**
     * @param $result
     * @return string
     */
    public function getHtmlContent($result){
            $html = '<style type="text/css">table,thead,thead,th,td{font-family: Tahoma,"宋体";font-size: 12px;margin: 0;padding: 0;text-align: center;border: 0;}table{border-collapse:collapse;}thead th{margin: 0;padding-top: 5px;padding-bottom: 5px;color: #fff;line-height: 18px;background: #3295D3;border: 1px #B0CEDD solid;}tbody th{margin: 0;padding-top: 5px;padding-bottom: 5px;font-size: 10px;font-weight: normal;line-height: 20px;border: 1px #B0CEDD solid;}</style><table><thead><th width="70px">日期</th><th width="50px">车次</th><th width="60px">出发/<br />到达</th><th width="60px">出发时间/<br />到达时间</th><th width="50px">历时</th><th width="40px">商务座</th><th width="40px">特等座</th><th width="40px">一等座</th><th width="40px">二等座</th><th width="50px">高级软卧</th><th width="40px">软卧</th><th width="40px">硬卧</th><th width="40px">软座</th><th width="40px">硬座</th><th width="40px">无座</th><th width="40px">其他</th></thead><tbody>';
            foreach ($result as $num) {
                foreach ($num as $date => $train) {
                    foreach ($train as $key => $value) {
                        $html .= '<tr><th>'.$value['start_train_date'].'</th><th>'.$key.'</th><th>';
                        $html .= $value['from_station_name'].'/'.$value['to_station_name'].'</th><th>'.$value['start_time'].'/'.$value['arrive_time'].'</th><th>'.$value['lishi'];
                        $html .= '</th><th>'.$value['swz_num'].'</th><th>'.$value['tz_num'].'</th><th>'.$value['zy_num'].'</th><th>'.$value['ze_num'];
                        $html .= '</th><th>'.$value['gr_num'].'</th><th>'.$value['rw_num'].'</th><th>'.$value['yw_num'];
                        $html .= '</th><th>'.$value['rz_num'].'</th><th>'.$value['yz_num'].'</th><th>'.$value['wz_num'].'</th><th>'.$value['qt_num'].'</th></tr>';
                    }
                }
            }
            return $html.'</tbody></table>';
        }


    /**
     * @param $content
     */
    public function sendMail($content){
            $mail = new PHPmailer;
            $mail->isSMTP();
            $mail->SMTPDebug = 1;
            $mail->CharSet = 'utf-8';
            $mail->Host = 'smtp.163.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'username@163.com';
            $mail->Password = 'password';
            //$mail->SMTPSecure = false;
            $mail->Port = 25;

            $mail->From = 'username@163.com';
            $mail->FromName = '12306提醒';
            $mail->addAddress('123456@qq.com');
            $mail->isHTML(true);

            $mail->Subject = '居然还有票～';
            $mail->Body    = $content . '<br /><br /><br /><a href="https://kyfw.12306.cn/otn/lcxxcx/init">赶紧去12306抢票吧～</a>';
            if(!$mail->send()) {
                echo 'Message could not be sent.';
                echo 'Mailer Error: ' . $mail->ErrorInfo;
            } else {
                echo 'Message has been sent';
            }
        }
    }

    //================================================================================================================
    // 分割线
    //================================================================================================================


    // 日期
    $date_arr = array(
            '2015-02-15',
            '2015-02-16',
            '2015-02-17',
        );

    // 请查找 station.txt，可以多个
    $start_station = array('SHH');
    $end_station = array('BJP');

    //指定车次,可以留空
    // $train_arr = array()
    $train_arr = array(
            'G104',
            'G2',
            '1462',
            'T110'
        );

    /*
    swz_num		商务座
    tz_num		特等座
    zy_num		一等座
    ze_num		二等座
    gr_num		高级软卧
    rw_num		软卧
    rz_num		软座
    yw_num		硬卧
    yz_num		硬座
    wz_num		无座
    qt_num		其他
    yb_num		?
    gg_num		?
    */
    $type_arr = array(
            'swz_num'=>'0',
            'tz_num'=>'0',
            'zy_num'=>'1',
            'ze_num'=>'1',
            'gr_num'=>'1',
            'rw_num'=>'1',
            'rz_num'=>'1',
            'yw_num'=>'1',
            'yz_num'=>'1',
            'wz_num'=>'0',
            'qt_num'=>'0'
        );


    $guonian = new Huijia($date_arr, $start_station, $end_station, $train_arr, $type_arr);
    $result = $guonian->checkTicket();
    if(!$result){
        exit("Unfortunately~~\n");
    }else{
        $guonian->sendMail($result);
    }

?>
